# EpCtrl.com (Episode Control)

Official service url: [http://epctrl.com](http://epctrl.com).

Episode Control is a simple service based on epguides.com's open database of tv-series. The service allows you to manage a list of your favourite tv-series, you check off any episodes you've viewed and get a overview of the next available episodes.

## Requirements
If you'd like to fork this project there are a few steps to making it operational.
* You need a working **LAMP-stack** (wmap / mamp might work too).
* You need a empty **database** + user with **all privileges**.

## Getting started
* Clone this repository
* Copy ```phinx.yml.example``` to ```phinx.yml``` and edit it to match your database credentials.
* Copy ```application/configs/application.ini.example``` to ```application/configs/application.ini``` and edit it to match you details.
* Migrate the database by running the following command: ```./vendor/robmorgan/phinx/bin/phinx migrate```
