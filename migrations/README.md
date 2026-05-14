# Migrations

`config.php`'deki `ensureAllTables()` fonksiyonu uygulama başlangıcında tabloları ve indeksleri idempotent olarak oluşturur. Bu klasör, versiyonlu şema değişikliklerini kalıcı kayıt altına almak içindir.

## Kullanım

Yeni bir değişiklik olduğunda sıralı şekilde yeni dosya ekleyin:

```
001_initial_schema.sql
002_add_indexes.sql
003_blog_status_column.sql
...
```

Her dosyanın başına yorum olarak kısa bir açıklama ve çalıştırılma şartı yazın.

## Üretim akışı (ileride)

Şu an migrations yalnızca referans dosyalar. Ölçek büyüdüğünde Phinx veya basit bir `php migrate.php` script'i ile `schema_migrations` tablosu üzerinden uygulanabilir.
