<?php

namespace App\Service\Docs;

use App\Entity\App;

class Welcome extends DocBuilder implements DocInterface
{
    public function build()
    {
        return $this

            ->text('The XIVAPI provides a massive amount of FINAL FANTASY XIV game data in a JSON format via 
                a REST API. You can fetch information on all sorts of game content that has been discovered and 
                mapped in the SaintCoinach Schema. In addition it provides Character, Free Company, Linkshell, PvPTeams 
                and Lodestone information!')

            ->h6('BETA')
            ->text('Please consider the current version "BETA", it is still been heavily developed and things
                may change quite drastically. I highly recommend getting on Discord and helping shape the API :)')

            ->gap()

            //
            // ENDPOINTS
            //
            ->h6("Endpoints")
            ->table(['Production', 'Staging'], [
                [ 'https://xivapi.com', '*TBD*' ]
            ])
            ->note('All routes except game content can be accessed both by UpperCasing and lowercase. 
                This is intentional, the game content endpoints are Case-Sensitive UpperCasing, thus: 
                `AchievementCategory` will work, but `achievementcategory` will not as this is how they 
                are in the in-game files. The API provides endpoints in the same style for anything custom, 
                eg: `Characters`, `Lodestone/WorldStatus`')
            
            ->gap(2)

            //
            //  GLOBAL QUERIES
            //
            ->h6('Global Queries')
            ->text('These query parameters can be set on all endpoints')

            // language=X
            ->h3('language')
            ->usage('{endpoint}/Item/1675?language=fr')
            ->text('This will tell the API to handle the request and the response in the specified language.')
            ->queryParams([
                [ '`en`', 'English' ],
                [ '`ja`', 'Japanese' ],
                [ '`de`', 'German' ],
                [ '`fr`', 'French' ],
                [ '`cn`', 'Chinese' ],
                [ '`kr`', 'Korean' ],
            ])

            ->text('To help with development; you may want to use the simplified field `Name`. 
                If you can provide the query `language=fr` and now `Name` will be the French name. 
                This is also extended to other string fields such as Descriptions.')

            ->text('Search will use the language parameter to decide which field to query the `string` 
                against, for example: `language=fr&string=LeAwsome` will search for `LeAwesome` on the 
                field `Name_fr`.')

            ->gap()

            ->h3('pretty')
            ->usage('{endpoint}/Item/1675?pretty=1')
            ->text('This will provide a nice pretty JSON response, this is intended for debugging 
                purposes. Don\'t use this in production as it adds weight to the response and queries will be longer.')
            ->bold('Example difference')
            ->code('{"ClassJobCategory.Name":"PLD","ID":1675,"Icon":"\/img\/ui\/game\/icon4\/1\/1675.png","Name":"Curtana"}', 'json')
            ->text('Will become:')
            ->json('{  
                "ClassJobCategory.Name": "PLD",
                "ID": 1675,
                "Icon": "\/img\/ui\/game\/icon4\/1\/1675.png", "Name":
                "Curtana"
            }')
            ->gap()
    
            // columns
            ->h3('columns')
            ->usage('{endpoint}/Item?columns=ID,Icon,Name&pretty=1')
            ->text('This is a global query and can be used on any endpoint.')
            ->text('This query allows specific columns to be pulled from the data and exclude the rest of
                the JSON response. This allows you narrow down to specific bits of information and reduce
                the size of the payload to your application. For nested data you can use dot notation
                (to a max of 10 nested nodes) to access it, for example:')
            ->usage('{endpoint}/Item?columns=ID,Icon,Name,ClassJobCategory.Name')
            ->text('- ID, Icon, Name, ClassJobCategory.Name (nested)')
            ->json('[
                {
                    "ID": 2901,
                    "Icon": "\/i\/040000\/040635.png",
                    "Name": "Choral Chapeau",
                    "ClassJobCategory": {
                        "Name": "BRD"
                    }
                },
                {
                    "ID": 2902,
                    "Icon": "\/i\/041000\/041041.png",
                    "Name": "Healer\'s Circlet",
                    "ClassJobCategory": {
                        "Name": "WHM"
                    }
                },
                {
                    "ID": 2903,
                    "Icon": "\/i\/040000\/040634.png",
                    "Name": "Wizard\'s Petasos",
                    "ClassJobCategory": {
                        "Name": "BLM"
                    }
                }
            ]')
            ->text('Sometimes a piece of data will have an array of sub data, for example:')
            ->json('{
                "ID": 1,
                "Name": "Example",
                "Items": [
                    {
                        "Name": "foo"
                    },
                    {
                        "Name": "bar"
                    }
                ]
            }')
            ->bold('List Content')
            ->text('If any response is a "List" (contains: `Pagination` and `Results` at the top level
                then the `columns=X` will be performed on each result as opposed to globally, this means
                you can reduce the data for every list item in a search or on `/<ContentName>` lists.')
            
            ->bold('Nested arrays')
            ->text('If a field is an array of data, the entire array contents would return, you could reduce
                this further if you wish:')
            ->text('To access the data in `Items` individually you could do')
            ->code('columns=Items.0.Name,Items.1.Name')
            ->text('If you imagine an array having 50 items, this could be tedious and will eat into your maximum
                column count. You can therefore use a count format, eg:')
            ->code('columns=Items.*50.Name')
            ->text('This will return 50 rows from the column `Items` using the index `Name`, even if there
                are only 30 legitimate columns, 50 fields will be returned. This is intentional so you can
                build models knowing at all times X number of columns will return. You can use the FFXIV CSV
                files to know exactly how many there are exactly.')

            ->gap(2)

            //
            // API Keys
            //
            ->h6('Apps & API Keys')
            ->text('The API is very public and can be used without any keys but this will have 
                some restrictions, for example non-key apps have a Rate-Limit of 5/second.')
            ->list([ 'You can create a developer app by going to: [Applications](/app)' ])
            ->gap()
            ->h3('key')
            ->usage('{endpoint}/Item?key=xxxx')
            ->text('Keys provide usage statistics and have rate limits on them to prevent abuse of 
                the API. You can re-generate your API key at any time, make as many apps as you like 
                and use them freely.')
            
            ->table(
                [ 'Default Rate Limit', 'App Rate Limit' ],
                [
                    [ App::DEFAULT_RATE_LIMIT, App::LV2_RATE_LIMIT ]
                ]
            )
            ->text('Rate limits are per hashed IP, per second, if you need a higher limit. Please ask in Discord.')

            ->text('A default key also has the following restrictions as they interact with The Lodestone:')
            ->list([
                'Cannot delete characters',
                'Cannot delete free companies',
                'Cannot delete linkshells',
                'Cannot delete pvp teams',
                'Cannot request character bio verification',
                'Cannot request dev forum posts',
                'Cannot request any market information'
            ])
            ->gap()

            ->h3('tags')
            ->usage('{endpoint}/servers?key=xxxx&tags=lorem,ipsum')
            ->text('You can add tracking counters to your app for whatever purpose using "tags". Separate tags 
                with commas and they will appear in your dashboard with a counter next to them. You can have 
                as many tags you would like and counts will store for a period of 30 days before taping off and 
                being removed if they become inactive.')
            ->text('A tag must be alpha numeric and allows dashes and underscores.')
            ->gap()

            ->h4('Rate-Limiting')
            ->text('Apps have their own individual rate limits. This is per ip per key. IPs are not stored in 
                the system but instead are hashed and used as a tracking point for that "second". Your number of 
                hits per second can be viewed in your app as the current requests per/second can be seen.')
            ->gap()
            
            ->h4('Ints')
            ->text('The API will return `ints` as `strings` whenever an numeric value is a length of 10 or more,
                this means that unix timestamps, FC/LS IDs and any other long numbers will return as string
                and not cause overflow issues.')
            ->gap(2)

            //
            // SaintCoinach
            //
            ->h6('SaintCoinach Schema')
            ->text('You can find the Saint-Coinach schema here:')
            ->list([ 'https://github.com/ufx/SaintCoinach/blob/master/SaintCoinach/ex.json' ])
            ->text('The schema is a huge JSON file that describes the EXD files found in the FFXIV game files. 
                Many community members take time to datamine and understand the way the EXD files are mapped and 
                this file helps describe it in a universal format.')

            ->h5('Special fields and schema differences')
            ->text('Some fields in the API are not part of the SaintCoinach Schema and have been implemented for 
                ease of use. For example: `NPC.Quests` provides all quests related to the NPC.')
            ->text('Other files are API specific, for example: GamePatch is the API\'s patching system, 
                `GameContentLinks` are reverse links from one content to another. Make sure to use the schema 
                endpoint on the API to see what you can obtain :)')
            ->text('In addition, to make things more simpler in the templates, some fields have been globally 
                simplified for example a contents "`Singular`" field is known as "`Name`", a "`Masculinity`"  
                would also be converted to "`Name`" with "`Feminine`" converted to "`NameFemale`"')

            ->h5('Field name change list')
            ->table(
                [ 'Content name', 'Schema field name', 'API field name' ],
                [
                    [ 'BNpcName', 'Singular', 'Name' ],
                    [ 'ENpcResident', 'Singular', 'Name' ],
                    [ 'Mount', 'Singular', 'Name' ],
                    [ 'Companion', 'Singular', 'Name' ],
                    [ 'Title', 'Masculine', 'Name' ],
                    [ 'Title', 'Feminine', 'NameFemale' ],
                    [ 'Race', 'Masculine', 'Name' ],
                    [ 'Race', 'Feminine', 'NameFemale' ],
                    [ 'Tribe', 'Masculine', 'Name' ],
                    [ 'Tribe', 'Feminine', 'NameFemale' ],
                    [ 'Quest', 'Id', 'TextFile' ],
                ]
            )
            ->gap()

            ->text('Another minor thing to be aware of is confusing names for various different content schemas, 
                for some reason Square-Enix have named stuff in the game files that do not match the in-game 
                representation. Below is a table of common content types that you will see in game and their 
                data-file name:')

            ->table(
                [ 'Content name', 'Schema name', 'details' ],
                [
                    [ 'Minions', 'Companion', 'I do not know why SE call them Companions' ],
                    [ 'Chocobo Companion', 'Buddy', 'Again, confusing with Minions...' ]
                ]
            )
            ->line()

            //
            // Note
            //
            ->h5('HTTP or HTTPS?')
            ->text('**Please use: `https`**')
            ->text('Both are currently supported on the API as there is no sensitive data being provided 
                or available via the API. This may change in future so please try use HTTPS to 
                avoid your applications breaking.')

            ->get();
    }
}
