<?php
setlocale( LC_ALL, 'de_DE' );
date_default_timezone_set('Europe/Berlin');
require_once realpath(dirname(__FILE__)) . '/index/autoload.php';

$configReader = new \MoveElevator\FeatureIndex\Service\ConfigReader();
$ioService = new \MoveElevator\FeatureIndex\Service\IOService();
$templateService = new \MoveElevator\FeatureIndex\Service\TemplateService();

$config = $configReader->initConfig();

?>

<html>
    <head>
        <meta charset='utf-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1, minimum-scale=1'>
        <link rel='icon' type='image/png' href='.fbd/logo.png' />

        <title><?php echo strip_tags($config['projectName']) ?></title>
        <link rel="stylesheet" href="https://unpkg.com/@picocss/pico@1.5.12/css/pico.min.css">
        <link rel="stylesheet" href=".fbd/index/assets/css/style.css">
        <style>
            <?php if (file_exists('.fbd/background.png')) {
                    echo "body {background-image: url('.fbd/background.png');background-repeat: repeat-y;background-attachment: fixed;background-position: right;background-size: contain;min-height: 100vh;}";
                  }
            ?>
        </style>
    </head>
    <body>
        <progress style="color:red" value="<?php echo $ioService->getDiskFullSpacePercent() ?>" max="100"></progress>
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
                    <li>
                        <img title="<?php echo $config['projectName'] ?>" alt="<?php echo $config['projectName'] ?>" width="100px" src=".fbd/logo.png" />
                    </li>
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
