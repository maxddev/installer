# Helpflow Installer

Before attempting to use the installer please make sure you have purchased a license for Helpflow. This will give you access to the repository allowing this installer to function.

## Installation

After a license has been purchased, clone this repository to a location on your system and then run `composer install` within the root directory of the repository.

While installing, add the directory path to the repository to your systems PATH variable. This will allow the `helpflow` installer to run from anywhere within your system.

## Installing Helpflow

Once you have setup the installer, simply navigate in the command line to the root of your application and run

    helpflow install

You will be asked which admin type you want to install, after selecting, the installer will proceed to install Helpflow.

Once installated you will need to add a link to your help desk within your application for your customers and support staff alike. The route can be generated with `route('helpflow.list-tickets')`.

## Adding Support Staff

You will also need to setup who can access the support staff functionality of your help desk. If you are using Helpflow Generic or Helpflow Spark integrations, this can be done by defining the email address of any user account that should have support staff privledges within the `helpflow-config.php` file.

## Updating Helpflow

To update helpflow to the latest version, simply navigate in the command line to your application and run

    helpflow update

You will be asked which admin type you are using, at which point the update will proceed.