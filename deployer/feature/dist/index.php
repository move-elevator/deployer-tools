<?php
setlocale( LC_ALL, 'de_DE' );
date_default_timezone_set('Europe/Berlin');
require_once realpath(dirname(__FILE__)) . '/index/autoload.php';

$configReader = new \MoveElevator\FeatureIndex\Service\ConfigReader();
$ioService = new \MoveElevator\FeatureIndex\Service\IOService();
$templateService = new \MoveElevator\FeatureIndex\Service\TemplateService();

$config = $configReader->initConfig();
// the title is set in the project's deploy.php and may contain markup for the heading, e.g. <em>
$projectTitle = htmlspecialchars(strip_tags($config['projectName']), ENT_QUOTES);
$logo = current(array_filter(['.fbd/logo.svg', '.fbd/logo.png'], 'file_exists'));

// feature instances are test systems, keep the overview out of search engines
header('X-Robots-Tag: noindex, nofollow');

?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset='utf-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1, minimum-scale=1'>
        <meta name='robots' content='noindex, nofollow'>
        <?php if ($logo): ?>
        <link rel='icon' type='<?php echo str_ends_with($logo, '.svg') ? 'image/svg+xml' : 'image/png' ?>' href='<?php echo $logo ?>' />
        <?php endif ?>

        <title><?php echo $projectTitle ?></title>
        <link rel="stylesheet" href=".fbd/index/assets/css/pico.min.css">
        <link rel="stylesheet" href=".fbd/index/assets/css/style.css">
        <script src=".fbd/index/assets/js/index.js" defer></script>
        <style>
            <?php if (file_exists('.fbd/background.png')) {
                    echo "body {background-image: url('.fbd/background.png');background-repeat: repeat-y;background-attachment: fixed;background-position: right;background-size: contain;min-height: 100vh;}";
                  }
            ?>
        </style>
    </head>
    <body>
        <?php echo $templateService->renderDiskSpace($ioService) ?>
        <header class="container">
            <nav>
                <ul>
                    <li>
                        <hgroup>
                            <h1><?php echo $config['projectName'] ?> <span class="app-type" aria-hidden="true"><?php echo $templateService->getApplicationType($config['applicationType']) ?></span></h1>
                            <p>Feature Branch Deployment · Test Systems</p>
                        </hgroup>
                    </li>
                </ul>
                <ul>
                    <?php echo $templateService->renderAdditionalLinks($config['additionalLinks']) ?>
                    <?php if ($logo): ?>
                    <li>
                        <img class="logo" title="<?php echo $projectTitle ?>" alt="<?php echo $projectTitle ?>" height="36" src="<?php echo $logo ?>" />
                    </li>
                    <?php endif ?>
                </ul>
            </nav>
        </header>
        <main class="container">
            <?php $entries = $ioService->getDirectoryEntries(realpath(dirname(__FILE__)) . '/..') ?>
            <div class="toolbar">
                <?php echo $templateService->renderOverview($entries, $ioService) ?>
                <!-- revealed by index.js, filtering needs JavaScript -->
                <label class="filter" hidden>
                    <span class="visually-hidden">Filter instances</span>
                    <input type="search" id="instance-filter" placeholder="Filter by branch, issue or summary" autocomplete="off">
                </label>
            </div>
            <?php echo $templateService->renderInstances((new \MoveElevator\FeatureIndex\Utility\EntryUtility())->groupEntries($entries)) ?>
        </main>
    </body>
</html>
