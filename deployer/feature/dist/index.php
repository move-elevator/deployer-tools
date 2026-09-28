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
        <style>
            <?php if (file_exists('.fbd/background.png')) {
                    echo "body {background-image: url('.fbd/background.png');background-repeat: repeat-y;background-attachment: fixed;background-position: right;background-size: contain;min-height: 100vh;}";
                  }
            ?>
        </style>
    </head>
    <body>
        <?php echo $templateService->renderDiskSpace($ioService) ?>
        <header class="container" style="padding-bottom: 0">
            <nav>
                <ul>
                    <li>
                        <hgroup>
                            <h2><?php echo $config['projectName'] ?> <div style="display: inline-block; width: 25px; position: absolute; margin-left: 5px;"><?php echo $templateService->getApplicationType($config['applicationType']) ?></div></h2>
                            <h3 data-tooltip="The feature branch deployment describes the deployment and initialization process of multiple application instances on the same host. The feature instances are used for testing purposes and managing the release workflow.">Feature Branch Deployment</h3>
                        </hgroup>
                    </li>
                </ul>
                <ul>
                    <?php echo $templateService->listAdditionalLinks($config['additionalLinks']) ?>
                    <?php if ($logo): ?>
                    <li>
                        <img title="<?php echo $projectTitle ?>" alt="<?php echo $projectTitle ?>" width="100" src="<?php echo $logo ?>" />
                    </li>
                    <?php endif ?>
                </ul>
            </nav>
        </header>
        <main class="container">
            <section>
                <table>
                    <tbody>
                        <?php
                        /**
                         * List all available feature branches
                         */

                        $entries = $ioService->getDirectoryEntries(realpath(dirname(__FILE__)) . '/..');
                        echo $templateService->renderEntries($entries);
                        ?>
                    </tbody>
                </table>
            </section>
        </main>
    </body>
</html>
