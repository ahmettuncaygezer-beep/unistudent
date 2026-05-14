<?php
declare(strict_types=1);

/**
 * Haftalık dijest oluşturucu (pure function — no auth/exit).
 * API ve cron tarafından ortak kullanılır.
 */
function generate_weekly_digest(\PDO $pdo, int $userId): array {
    $now     = new DateTimeImmutable('now');
    $weekAgo = $now->modify('-7 days');
    $twoWeek = $now->modify('-14 days');

    $stmt = $pdo->prepare(
        "SELECT category, type, SUM(amount) AS total, COUNT(*) AS cnt
         FROM transactions
         WHERE user_id = ? AND transaction_date >= ?
         GROUP BY category, type"
    );
    $stmt->execute([$userId, $weekAgo->format('Y-m-d H:i:s')]);
    $week = $stmt->fetchAll();

    $stmt = $pdo->prepare(
        "SELECT SUM(amount) AS total FROM transactions
         WHERE user_id = ? AND type='expense'
           AND transaction_date BETWEEN ? AND ?"
    );
    $stmt->execute([$userId, $twoWeek->format('Y-m-d H:i:s'), $weekAgo->format('Y-m-d H:i:s')]);
    $priorTotal = (float)($stmt->fetch()['total'] ?? 0);

    $curExpense = 0; $curIncome = 0; $catTotals = [];
    foreach ($week as $w) {
        if ($w['type'] === 'expense') {
            $curExpense += (float)$w['total'];
            $catTotals[$w['category']] = ($catTotals[$w['category']] ?? 0) + (float)$w['total'];
        } else {
            $curIncome += (float)$w['total'];
        }
    }
    arsort($catTotals);
    $topCat = array_key_first($catTotals);
    $delta  = $priorTotal > 0 ? (($curExpense - $priorTotal) / $priorTotal) * 100 : 0;

    $g = $pdo->prepare("SELECT title, target_amount, current_amount FROM user_goals WHERE user_id=? AND is_completed=0 LIMIT 3");
    $g->execute([$userId]);
    $goals = $g->fetchAll();

    $s = $pdo->prepare(
        "SELECT name, amount, next_billing FROM subscriptions
         WHERE user_id=? AND is_active=1 AND next_billing BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)"
    );
    $s->execute([$userId]);
    $dueSubs = $s->fetchAll();

    $tips = [];
    if ($delta > 15)      $tips[] = sprintf('Bu hafta geçen haftaya göre %%%.0f daha fazla harcadın — frene basma zamanı.', $delta);
    elseif ($delta < -10) $tips[] = sprintf('Harikasın! Geçen haftaya göre %%%.0f tasarruf ettin.', abs($delta));
    if ($topCat) $tips[] = sprintf('En çok harcadığın alan: %s (%s₺).', $topCat, number_format($catTotals[$topCat], 0, ',', '.'));
    if ($dueSubs) $tips[] = count($dueSubs) . ' abonelik önümüzdeki 7 gün içinde yenilenecek.';
    if ($goals)   $tips[] = 'Aktif ' . count($goals) . ' tasarruf hedefin var — bu hafta bir adım daha at!';

    $summary = null;
    $claudeKey = function_exists('env') ? env('CLAUDE_API_KEY', '') : '';
    if ($claudeKey && function_exists('curl_init')) {
        $summary = _call_claude_digest($claudeKey, [
            'week_expense' => $curExpense,
            'week_income'  => $curIncome,
            'prior_expense'=> $priorTotal,
            'top_category' => $topCat,
            'top_amount'   => $catTotals[$topCat] ?? 0,
            'active_goals' => count($goals),
            'due_subs'     => count($dueSubs),
        ]);
    }

    return [
        'period' => ['from' => $weekAgo->format('Y-m-d'), 'to' => $now->format('Y-m-d')],
        'totals' => [
            'expense' => round($curExpense, 2),
            'income'  => round($curIncome, 2),
            'net'     => round($curIncome - $curExpense, 2),
            'delta_pct_vs_prior' => round($delta, 1),
        ],
        'top_categories' => array_slice(array_map(
            fn($k, $v) => ['category' => $k, 'amount' => round($v, 2)],
            array_keys($catTotals), $catTotals), 0, 5),
        'active_goals' => $goals,
        'due_subs'     => $dueSubs,
        'tips'         => $tips,
        'ai_summary'   => $summary,
    ];
}

function _call_claude_digest(string $key, array $stats): ?string {
    $prompt = sprintf(
        "Sen bir Türk üniversite öğrencisinin samimi finans koçusun. 3 kısa paragraf (toplam 120 kelime) samimi, teşvik edici bir özet yaz. Emoji kullan.\n\nGider: %s₺ / Gelir: %s₺ / Geçen hafta gider: %s₺\nEn çok kategori: %s (%s₺)\nAktif hedef: %d / 7 gün içinde abonelik: %d",
        number_format($stats['week_expense'], 0), number_format($stats['week_income'], 0),
        number_format($stats['prior_expense'], 0),
        $stats['top_category'] ?? '—', number_format($stats['top_amount'], 0),
        $stats['active_goals'], $stats['due_subs']
    );
    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-api-key: ' . $key,
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => 'claude-haiku-4-5-20251001',
            'max_tokens' => 400,
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ]),
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200 || !$res) return null;
    $j = json_decode($res, true);
    return $j['content'][0]['text'] ?? null;
}
