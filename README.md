# Arabic-Wiki-Bot
Source code of Wikipedia Arabic Bot, built using Peachy for Arabic wikipedia.

## How to Use
Assuming that you are using it on wmflabs.
- create peachy_config.cfg (copy the below code, then edit bot username & password)
```
[config]
baseurl = "https://ar.wikipedia.org/w/api.php"
username = "NameOfBot"
password = "???"
maxlag = "0"
editsperminute = "0"
httpecho = "true"
consumerkey = "foo"
consumersecret = "foobar"
accesstoken = "bla"
accesssecret = "blabar"
oauthurl = "https://ar.wikipedia.org/wiki/%D8%AE%D8%A7%D8%B5:%D8%A3%D9%88_%D8%A3%D9%88%D8%AB"
method = "legacy"
```
- configure config.php (enable jobs there)
- add to cron: 
`0 */3 * * * jstart $HOME/path-to/bot.php` 

