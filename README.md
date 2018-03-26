# **GAB**
Gekko Automated Backtests

*** **THIS IS ALPHA, ANYTHING BREAKING MIGHT CHANGE AT ANY GIVEN TIME** ***

If something does not work, it simply does not work since time hasn't existed yet to get it working.

###### Breaking changes
26/3 2018
Cleaned up most stuff and optimized many more + added a lot of things 'under the hood'. A lot of changes and changes to things such as the databases etc. This make this a version _incompatible_ with the prior (first released) version.

If you want to keep your old results copy your old `gab` folder to somewhere else and then clone this repo again.

#### Why
I needed a way to run backtests for Gekko in a 'brute-force' manor automated and with multi-threading.
I also needed a way to compare all these runs and get extra data such as win% etc.

##### Prerequisites (required)

1. Gekko installed and working
2. Webserver + PHP (preferrably 7.1+) with cURL and PDO SQLite
3. A user with write access

##### How-to

1. Check config in system/conf.php and make sure $server is pointing at YOUR running Gekko instance e.g. localhost:3000/api/
2. Go to your-localhost/gab/select.php
3. Run something..
4. After you got some runs goto your-localhost/view.php and check all the results

##### Dynamic parameters

GAB uses dynamic parameters, these work for all strategy paramers.
Example, RSI BULL/BEAR:

```
# SMA
SMA_long = 100:1000,100
SMA_short = 10:90,10

# BULL
BULL_RSI = 5:20,5
BULL_RSI_high = 70:90,5
BULL_RSI_low = 40:60,5

# BEAR
BEAR_RSI = 5:20,5
BEAR_RSI_high = 40:60,5
BEAR_RSI_low = 10:30,5
```

The format is <FROM>:<TO>,<STEPPING>.
This means that e.g. `10:20,5` will generate the range: `10,15,20`
The params are inclusive meaning that odd stepping e.g. `5:15,10` will become `5,10,15` - the first and last of range is always kept.


#### Donate or coffee?
People keeps asking about this so i'll just leave it here for future reference:
BTC: 15cZUi7VvmCQJLbPXYYWChHF3JpsBaYDtH
ETH: 0xe03c5eb9DF93360e3Bcfcd13012B7DeebbED6923
