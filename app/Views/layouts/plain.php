<!doctype html>
<html lang="th">
<head><?= (new App\Core\View())->partial('partials/head', ['title' => $title ?? null]) ?></head>
<body>
<?= $content ?>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
