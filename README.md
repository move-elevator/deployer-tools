<div align="center">

# Deployer Tools

[![CGL](https://img.shields.io/github/actions/workflow/status/move-elevator/deployer-tools/cgl.yml?label=cgl&logo=github)](https://github.com/move-elevator/deployer-tools/actions/workflows/cgl.yml)
[![Supported PHP Versions](https://img.shields.io/packagist/dependency-v/move-elevator/deployer-tools/php?logo=php)](https://packagist.org/packages/move-elevator/deployer-tools)

</div>

> The Deployer Tools combine multiple [deployer](https://deployer.org/) recipes for an improved deployment process and workflow.

- [Installation](#installation)
- [Feature Branch Deployment](#feature-branch-deployment)
- [Symfony](#symfony)
- [TYPO3](#typo3)
- [Standalone Tasks](#standalone-tasks)

## ✨ Features

The focus relies on reusable concluded tasks and the possibility to combine multiple deployment workflows, e.g. [Symfony](#symfony) and [Feature Branch Deployment](#feature-branch-deployment).

- predefined **deployment workflows** for *TYPO3* and *Symfony* applications
- compact **feature branch deployment**
- useful **standalone tasks** for extending existing workflows

## 🔥 Installation

[![Packagist](https://img.shields.io/packagist/v/move-elevator/deployer-tools?label=version&logo=packagist)](https://packagist.org/packages/move-elevator/deployer-tools)
[![Packagist Downloads](https://img.shields.io/packagist/dt/move-elevator/deployer-tools?color=brightgreen)](https://packagist.org/packages/move-elevator/deployer-tools)

```bash
composer require move-elevator/deployer-tools
```

Now you can adjust the `deploy.php` with the following features:

## 📝 Documentation

### Feature Branch Deployment

The feature branch deployment describes the deployment and initialization process of multiple application instances on the same host.

Read the [documentation](docs/FEATURE.md) for detailed installation instructions and further explanations.

### Symfony

The symfony deployment covers the deployment process for symfony applications.

Read the [documentation](docs/SYMFONY.md) for detailed installation instructions and further explanations.

### TYPO3

The TYPO3 deployment covers the deployment process for TYPO3 CMS applications.

Read the [documentation](docs/TYPO3.md) for detailed installation instructions and further explanations.

### Standalone Tasks
- MS Teams Notification
- Database backup
- [Security check](docs/SECURITY.md)
- [Development](docs/DEV.md)
- [Debug helper](docs/DEBUG.md)


## ©️ Credits

This project is a fork and further development of the [XIMA Deployer Tools](https://github.com/xima-media/xima-deployer-tools).

## ⭐ License

This project is licensed under [GNU General Public License 3.0 (or later)](LICENSE).
