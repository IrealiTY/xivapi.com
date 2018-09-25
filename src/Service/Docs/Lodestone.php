<?php

namespace App\Service\Docs;

class Lodestone extends DocBuilder implements DocInterface
{
    public function build()
    {
        return $this
            ->text('Returns information from the official "The Lodestone" website: 
                https://na.finalfantasyxiv.com/lodestone/')
            ->note('At this time (August 2018) only the NA Lodestone is being parsed, 
                therefore `?language=X` query will not effect the output. Work is in progress 
                to add multi-language support.')

            ->gap()
            
            // Lodestone
            ->route('/Lodestone')
            ->usage('{endpoint}/lodestone')
            ->text('WORK IN PROGRESS')
            ->text('Returns a collection of information from the Lodestone endpoints, included is:')
            ->list([
                'Banners',
                'News',
                'Topics',
                'Notices',
                'Maintenance',
                'Updates',
                'Status',
                'WorldStatus',
                'DevBlog (latest)',
                'DevPosts (latest)',
            ])
            ->text('The above data in this collection is generated every hour and cached, 
                providing a nice quick response.')
            ->gap()
            
            // News
            ->h6('News')
            ->route('/Lodestone/News')
            ->usage('{endpoint}/lodestone/news')
            ->text('Gets the latest news information from the homepage.')
            ->gap()
    
            // Notices
            ->h6('Notices')
            ->route('/Lodestone/Notices')
            ->usage('{endpoint}/lodestone/notices')
            ->text('Gets the latest notices.')
            ->gap()
    
            // Maintenance
            ->h6('Maintenance')
            ->route('/Lodestone/Maintenance')
            ->usage('{endpoint}/lodestone/maintenance')
            ->text('Gets the latest maintenance posts (Does not contain specific details such as times).')
            ->gap()
    
            // Updates
            ->h6('Updates')
            ->route('/Lodestone/Updates')
            ->usage('{endpoint}/lodestone/updates')
            ->text('Get a list of update posts.')
            ->gap()
    
            // Status
            ->h6('Status')
            ->route('/Lodestone/Status')
            ->usage('{endpoint}/lodestone/status')
            ->text('Get a list of status posts.')
            ->gap()
    
            // WorldStatus
            ->h6('World Status')
            ->route('/Lodestone/WorldStatus')
            ->usage('{endpoint}/lodestone/worldstatus')
            ->text('Get world status information on the FFXIV Servers.')
            ->gap()
    
            // DevBlogs
            ->h6('Dev Blogs')
            ->route('/Lodestone/DevBlogs')
            ->usage('{endpoint}/lodestone/devblogs')
            ->text('Get the latest DevBlogs information, this is pulled from an XML feed.')
            ->gap()
    
            // Feats
            ->h6('Feasts')
            ->route('/Lodestone/Feasts')
            ->usage('{endpoint}/lodestone/feasts')
            ->text('Get information on Feasts leaderboards')
            ->text('- `season=X` Pass along the season number to parse')
            ->text('- ?? - You can find more parameters on the feast page: 
                https://eu.finalfantasyxiv.com/lodestone/ranking/thefeast/')
            ->gap()
    
            // DeepDungeon
            ->h6('Deep Dungeon')
            ->route('/Lodestone/DeepDungeon')
            ->usage('{endpoint}/lodestone/deepdungeon')
            ->text('Get information on DeepDungeon rankings')
            ->text('- You can find more parameters on the deep dungeon page: 
                https://eu.finalfantasyxiv.com/lodestone/ranking/deepdungeon/')
            ->gap()
            
            ->note('All these routes query the lodestone directly in realtime. XIVAPI will 
                cache the lodestone response for a set amount of time. Please do not hammer these requests 
                or your IP will be blacklisted from the service.')
            
            ->get();
    }
}
