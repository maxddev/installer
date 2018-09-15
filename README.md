# Helpflow Installer

Before attempting to use the installer please make sure you have purchased a license for Helpflow. This will give you access to the repository allowing this installer to function.

## Installation

After a license has been purchased, clone this repository to a location on your system and then run `composer install` within the root directory of the repository. While installing, add the directory path to the repository to your systems PATH variable. This will allow the `helpflow` installer to run from anywhere within your system.

## Installing Helpflow

Once you have setup the installer, simply navigate in the command line to your application and run

    helpflow install

You will be asked which admin type you want to install, after selecting the installer will proceed to install Helpflow.

### Laravel Spark

As it's difficult to determine if you have modified any of the files, you will need to manually add a link to the Helpflow Kiosk panel to your Spark Kiosk menu. The route can be generated with `route('helpflow.admin.list-tickets')`.

**Also don't forget to add any support staff to `$developers` array in your `SparkServiceProvider` so they can access the staff members portion of Helpflow**

## Updating Helpflow

To update helpflow to the latest version, simply navigate in the command line to your application and run

    helpflow update

You will be asked which admin type you are using, at which point the update will proceed.