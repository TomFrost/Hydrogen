#Hydrogen
PHP 5.3+ Performance and Compatibility Library
[HydrogenPHP.com](http://www.hydrogenphp.com)

Alpha Notice
-------------
Hydrogen is currently in Alpha.  There are three important things to remember during this period:

- Alpha means that Hydrogen is in active development and should NOT be considered stable.  Using this in a production environment is very, very not recommended.  Anyone who attempts this should be capable of altering the code immediately if it breaks.  And if you do, please consider submitting those changes on GitHub!
- There will be **NO DEPRECATION** during Alpha.  Changed function/class/etc names and definitions will be called out in the Changelog with every release, so please read the Changelog  carefully when updating.
- Documentation is currently a little lacking in the code itself, but is being improved in great strides.  Refer to the guide below for documentation -- it will be updated with every new release.

Introduction
-------------
Hydrogen is a lightweight PHP 5.3+ toolkit to simplify the building of custom, dynamic web applications.  Its main focus is on making webapps run ridiculously fast, with performance and high-traffic scalability being the absolute highest concern.  Hydrogen has clocked in with impressive statistics, sometimes doubling the traffic that small servers with MySQL-intensive sites can handle.

Hydrogen's other main focus is portability, allowing your webapps to be installed on servers with different operating systems, SQL services, caching services, and semaphore capabilities installed without needing to change a single line of your code -- not even the database queries.  Hydrogen is planned to support a wide range of these services by version 1.0.  Please see the documentation link below for details.

Requirements
-------------
Hydrogen requires PHP 5.3 or later.  It makes heavy use of namespaces and other features introduced with this version of PHP, and cannot be ported back to earlier PHP versions.

Use of an opcode cache such as XCache is highly recommended, as Hydrogen is able to run entirely without stat() calls when paired with an opcode cache.  Also recommended (but optional) is a RAM-caching engine such as memcached.

Documentation
--------------
While there is _some_ PHPDoc-style documentation in the code, it's not currently complete.  The best up-to-date Hydrogen guide available is here:

**[webdevRefinery.com Hydrogen Overview](http://www.webdevrefinery.com/forums/topic/1440-hydrogen-overview/)**

And two specialized tutorials for the new and robust Hydrogen Templating Engine:

**[Hydrogen Templates for Front-End Developers](http://www.webdevrefinery.com/forums/topic/6404-hydrogen-templates-for-front-end-developers/)**

**[Hydrogen Templates for Back-End Developers](http://www.webdevrefinery.com/forums/topic/6686-hydrogen-templates-for-back-end-developers/)**

Support
--------
Informal support for Hydrogen is currently offered on **[webdevRefinery.com](http://www.webdevrefinery.com)**.  Ask away in the PHP forum!

Legal
------
Use of Hydrogen implies agreement with its software license, available in the **LICENSE** file.  This license is subject to change from release to release, so before upgrading to a new version of Hydrogen, please review its license.

Credits
--------
Hydrogen was created by [Tom Frost](http://www.frosteddesign.com).
Contributors can be found in the [GitHub Contributor Listing](http://github.com/TomFrost/Hydrogen/contributors).