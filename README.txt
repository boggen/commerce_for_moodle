Commerce 
a shopping cart for moodle
IN DEVELOPMENT, is not a working shopping cart application yet.

===============
INITIAL INFO

disscosuion athttps://moodle.org/mod/forum/discuss.php?d=276975 open source e-commerce for moodle

moodle tracker... https://tracker.moodle.org/browse/MDL-48712 

assume core files of moodle will be edited / required
currently setup as "fresh install" required, and not a simple upgrade is avilable.

moodle code is based off of...
https://github.com/boggen/moodle (2.9dev)

this commerce package is ported / based off of 
https://github.com/boggen/opencart

assume complete re-write of code coming from opencart, so as to adjust to moodle "coding styles, plugin types, plugin manager types, etc... other words using opencart as a pattern to copy from. with some code from it. ((trying to reduce amount of "re-inventing the wheel" per say))

Commerce = GPL 3 or higher lisence. (same lisence type as moodle and opencart)

==============
CURRENT STATUS 1/12/2015

---used advance renamer www.advancedrenamer.com to renamed all files in opencart to reflect what folder they are in. 
---to many folder depths, got tired of clicking through the folders. easier to just add the folder name to each file.
---cut/pasted above files. into individual plugin types / plugin managers (see below list of plugin mangers)
------localhost/moodle/checkout
------localhost/moodle/ordertot
------localhost/moodle/paygate
------localhost/moodle/prodinfo
------localhost/moodle/shipping
------localhost/moodle/storeinfo
---what did not fit into above, or have not yet done. is within 
------localhost/moodle/local/oc/garbagecrud.zip
---did a database dump of localhost/opencart. and imported into localhost/moodle. and then went through moodle "XMLDB editor" to adjust things to moodle requirements for database info. ((install.xml)) cut and pasted out what i could easily identify into, into above "individual plugin types above"
---plugin managers are based off of localhost/moodle/enrol  heavy commenting out and/or deleteing of functions was done. Other words just enough was done, so you can goto localhost/moodle -> site admin menu -> plugins -> (checkout, ordertotals, payment gateways, product info, shipping, store info) and see a listing of individual plugins for the various plugin managers.
---new to moodle and should of picked a different plugin manager type to copy from... to late now... 
---the result is plugin mangers have two different "code name schemes..."
------enrol
------enrollment

------checkout
------checkingout

------ordertot
------ordertotal

------paygate
------paymentgateway

------prodinfo
------productinfo

------shipping
------shipment

------storeinfo
------storemultiinfo

---playing it safe with folder and file names with 8.3 format (8 characters long for name, 3 characters long for file type extension)
---there is a 30 character limit set on database table names. as a result the 8.3 format seemed more appriprotate. to identify stuff from folder/file names and also in datbase table names, as well as code names ((see shorter of above nameing schemes))
---there is a limit of were you can use _ "underscores" for folder/file names. causing some additional issues. 
---there is a limit on use of CAPITAL LETTERS, nearly everything is lower case, with a couple exceptions within code. (no camel case) ThisIsCammelCaseEqualsNoNo



=============
INSTALL, 
standard installition should work with this Pre-loaded Commerce package.
COPY/PASTE from moodle readme.txt

For the impatient, here is a basic outline of the
installation process, which normally takes me only
a few minutes:

1) Move the Moodle files into your web directory.

2) Create a single database for Moodle to store all
   its tables in (or choose an existing database).

3) Visit your Moodle site with a browser, you should
   be taken to the install.php script, which will lead
   you through creating a config.php file and then
   setting up Moodle, creating an admin account etc.

4) Set up a cron task to call the file admin/cron.php
   every five minutes or so.


For more information, see the INSTALL DOCUMENTATION:

   http://docs.moodle.org/en/Installing_Moodle


Good luck and have fun!
Martin Dougiamas, Lead Developer

