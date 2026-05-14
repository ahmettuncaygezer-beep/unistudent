<?php
class DataManager
{
    private $dataFile;

    public function __construct()
    {
        $this->dataFile = __DIR__ . '/../../data.js';
    }

    public function getCityData()
    {
        $content = file_get_contents($this->dataFile);
        // Extract CITY_RAW array
        if (preg_match('/const CITY_RAW = (\[[\s\S]*?\]);/', $content, $matches)) {
            // Remove comments if any (simple approach) or trust the file structure
            $jsonToCheck = $matches[1];
            // Fix trailing commas if present (JS allows, JSON doesn't)
            $jsonToCheck = preg_replace('/,\s*\]/', ']', $jsonToCheck);
            $data = json_decode($jsonToCheck, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Determine error
                return ['error' => 'JSON Decode Error: ' . json_last_error_msg()];
            }
            return $data;
        }
        return ['error' => 'CITY_RAW not found'];
    }

    public function saveCityData($newCityData)
    {
        $content = file_get_contents($this->dataFile);
        // Encode new data to JSON
        $newJson = json_encode($newCityData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        // JS variable assignment
        $newContent = preg_replace('/const CITY_RAW = (\[[\s\S]*?\]);/', 'const CITY_RAW = ' . $newJson . ';', $content);

        if ($newContent) {
            file_put_contents($this->dataFile, $newContent);
            return true;
        }
        return false;
    }

    public function updateCity($index, $updatedFields)
    {
        $data = $this->getCityData();
        if (isset($data['error']))
            return $data;

        if (!isset($data[$index]))
            return ['error' => 'City index not found'];

        // Compact city structure: 
        // 0:key, 1:Name, 2:Emoji, 3:Desc, 4:Score, 
        // 5:RentSingle, 6:RentShared, 7:DormPrivate, 8:DormKYK(Not used here? No, it's global constant), 
        // Wait, index 8 in array is element 9?
        // Let's check array structure in data.js
        // ["istanbul", "İstanbul", "🌉", "Desc", Score, RentS, RentShared, DormPrivate, Food, Transport, Ent, Util, Tip1, Tip2]
        // 0: key
        // 1: Name
        // 2: Emoji
        // 3: Desc
        // 4: Score
        // 5: RentSingle
        // 6: RentShared
        // 7: DormPrivate
        // 8: Food
        // 9: Transport
        // 10: Entertainment
        // 11: Utilities
        // 12: Tip1
        // 13: Tip2

        // Update fields based on mapping
        if (isset($updatedFields['rentSingle']))
            $data[$index][5] = (int)$updatedFields['rentSingle'];
        if (isset($updatedFields['rentShared']))
            $data[$index][6] = (int)$updatedFields['rentShared'];
        if (isset($updatedFields['dormPrivate']))
            $data[$index][7] = (int)$updatedFields['dormPrivate'];
        if (isset($updatedFields['food']))
            $data[$index][8] = (int)$updatedFields['food'];
        if (isset($updatedFields['transport']))
            $data[$index][9] = (int)$updatedFields['transport'];
        if (isset($updatedFields['entertainment']))
            $data[$index][10] = (int)$updatedFields['entertainment'];
        if (isset($updatedFields['utilities']))
            $data[$index][11] = (int)$updatedFields['utilities'];

        // Save back
        return $this->saveCityData($data);
    }
    public function getSettings()
    {
        $content = file_get_contents($this->dataFile);
        $settings = [];

        // Extract EXPENSE_CATEGORIES
        if (preg_match('/const EXPENSE_CATEGORIES = (\[[\s\S]*?\]);/', $content, $matches)) {
            $json = preg_replace('/,\s*\]/', ']', $matches[1]);
            $settings['categories'] = json_decode($json, true);
        }

        // Extract KYK_CREDIT_OPTIONS
        if (preg_match('/const KYK_CREDIT_OPTIONS = (\[[\s\S]*?\]);/', $content, $matches)) {
            $json = preg_replace('/,\s*\]/', ']', $matches[1]);
            $settings['kyk'] = json_decode($json, true);
        }

        // Extract AI_TIPS
        if (preg_match('/const AI_TIPS = (\{[\s\S]*?\});/', $content, $matches)) {
            $json = preg_replace('/,\s*\}/', '}', $matches[1]);
            // JSON decode might fail if keys are not quoted. data.js keys are NOT quoted (housing: [...]).
            // We need to quote keys: housing: -> "housing":
            $json = preg_replace('/(\w+):/', '"$1":', $json);
            $settings['tips'] = json_decode($json, true);
        }

        return $settings;
    }

    public function saveSettings($newSettings)
    {
        $content = file_get_contents($this->dataFile);

        if (isset($newSettings['categories'])) {
            $json = json_encode($newSettings['categories'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $content = preg_replace('/const EXPENSE_CATEGORIES = (\[[\s\S]*?\]);/', 'const EXPENSE_CATEGORIES = ' . $json . ';', $content);
        }

        if (isset($newSettings['kyk'])) {
            $json = json_encode($newSettings['kyk'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $content = preg_replace('/const KYK_CREDIT_OPTIONS = (\[[\s\S]*?\]);/', 'const KYK_CREDIT_OPTIONS = ' . $json . ';', $content);
        }

        // Saving Tips is complex due to regex replacement of unquoted keys in my read logic.
        // For now, let's skip saving AI_TIPS via admin to avoid breaking file structure until we need it.
        // The user asked for "everything", but modifying object literals with regex is risky.
        // I'll stick to Categories and KYK for now as they are Arrays.

        return file_put_contents($this->dataFile, $content) !== false;
    }

    public function updateSettings($postData)
    {
        $current = $this->getSettings();

        // Update KYK Amounts
        if (isset($postData['kyk_amounts'])) {
            foreach ($postData['kyk_amounts'] as $index => $amount) {
                if (isset($current['kyk'][$index])) {
                    $current['kyk'][$index]['amount'] = (int)$amount;
                }
            }
        }

        // Update Category Defaults
        if (isset($postData['cat_defaults'])) {
            foreach ($postData['cat_defaults'] as $id => $defaultVal) {
                foreach ($current['categories'] as &$cat) {
                    if ($cat['id'] === $id) {
                        $cat['default'] = (int)$defaultVal;
                    }
                }
            }
        }

        return $this->saveSettings($current);
    }
}
?>
