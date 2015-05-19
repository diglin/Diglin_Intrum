# Diglin Intrum - The Intrum extension for the Magento eCommerce Platform - Developed by Diglin GmbH

## Description

Allow to use the creditor service of Justicia Intrum during a checkout process of a customer. Check if the customer can pay or not, useful in case of payment per Invoice

## License

Proprietary

## Support & Documentation

- Submit tickets - Contact (fee may apply, we will inform you how): support /at/ diglin.com

## System requirements

- Contact [intrum.com](http://www.intrum.com/) to get an account
- Magento CE >= 1.6.x to 1.9.x (for EE, please contact us)
- PHP >= 5.3.2
- PHP Curl, DOM, libxml

## Installation

### Via MagentoConnect

NaN

### Manually

```
git clone https://github.com/diglin/ricento.git
git submodule init
git submodule fetch
```

Then copy the files and folders in the corresponding Magento folders
Do not forget the folder "lib"

### Via modman

- Install [modman](https://github.com/colinmollenhour/modman)
- Use the command from your Magento installation folder: `modman clone https://bitbucket.org:diglin/magento-intrum.git`

#### Via Composer

- Install [composer](http://getcomposer.org/download/)
- Create a composer.json into your project like the following sample:

```
 {
    "require" : {
        "diglin/diglin_intrum": "1.*"
    },
    "repositories" : [
        {
            "type": "vcs",
            "url": "git@bitbucket.org:diglin/diglin_intrum.git"
        },
        {
            "type": "vcs",
            "url": "git@bitbucket.org:diglin/intrum.git"
        }
    ],
     "scripts": {
         "post-package-install": [
             "Diglin\\Intrum\\Composer\\Magento::postPackageAction"
         ],
         "post-package-update": [
             "Diglin\\Intrum\\Composer\\Magento::postPackageAction"
         ],
         "pre-package-uninstall": [
             "Diglin\\Intrum\\Composer\\Magento::cleanPackageAction"
         ]
     },
     "extra":{
       "magento-root-dir": "./"
     }
 }
 ```
- Then from your composer.json folder: `php composer.phar install` or `composer install`
- Do not pay attention to the yellow messages during composer installation process for this extension

## Uninstall

The module install some data and changes in your database. Deinstalling or deactivating the module will make some trouble cause of those data. You will need to remove those information by following the procedure below otherwise you will meet errors when using the Magento Backend It's a problem due to Magento, it's not related to the extension.

### Via MageTrashApp

An additional module called MageTrashApp may help you to uninstall this module in a clean way. Install it from [MageTrashApp](https://github.com/magento-hackathon/MageTrashApp)
If it is installed, go to your backend menu System > Configuration > Advanced > MageTrashApp, then click on the tab "Extension Installed", select the drop down option "Uninstall" of the module Diglin_Intrum and press "Save Config" button to uninstall
If you use this module, you don't need to make any queries in your database as explained below in case of manually uninstallation.

### Via Magento Connect 

NaN

### Modman

Same as MagentoConnect, modman can only remove files but cannot cleanup your database. So you can run the command `modman remove Diglin_Intrum` from your Magento root project however you will have to run the database cleanup procedure explained in the chapter "Manually" below.

### Manually

Remove the files or folders located into your Magento installation:
```
app/etc/modules/Diglin_Intrum.xml
app/code/community/Diglin/Intrum
```

## Author

* Diglin GmbH
* http://www.diglin.com/
* [@diglin_](https://twitter.com/diglin_)
* [Follow me on github!](https://github.com/diglin)
