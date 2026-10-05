<?php
/**
 * The code of extension/cjw_newsletter/bin/php/createoutput.php, moved into a class (#207 stage 1). The file extension/cjw_newsletter/bin/php/createoutput.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 */
/*
 * The original header of extension/cjw_newsletter/bin/php/createoutput.php:
 *
 *
 * File createoutput.php
 *
 * -script to create an newsletter edtion output for a siteaccess<br>
 * -to use the correct locale and SiteUrl<br>
 * -php extension/cjw_newsletter/bin/php/createoutput.php --object_id=102 --object_version=5 --output_format_id=0 --current_hostname=admin.jac-example.de.jac400.fw.lokal --www_dir=tmp/ --skin_name=default -s jac-example_user<br>
 * - --current_hostname :  only important for preview ( in testsystem )
 *
 * @copyright Copyright (C) 2007-2012 CJW Network - Coolscreen.de, JAC Systeme GmbH, Webmanufaktur. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package cjw_newsletter
 * @author Felix Woldt 2008
 * @subpackage phpscript
 * @filesource
 *
 */

namespace
{


/**
 * $hostname = www.test.de
 *
 * @param string $string
 * @param array $urlArray
 * @return unknown_type
 */
function generateAbsoluteLinks( $string , $urlArray )
{

    /*
     $hostUrlEZ = 'http://' .$hostNameAndUri;
     $hostUrlRoot = 'http://' .$hostName;
    */

    $hostUrlEZ   = $urlArray[ 'ez_url' ];
    $hostUrlRoot = $urlArray[ 'ez_root' ];

    $htmlPage = $string;
    $htmlPage = preg_replace("/url:/",              $hostUrlEZ,                         $htmlPage );
    $htmlPage = preg_replace("/src=\"\/design/",    'src="'.$hostUrlRoot.'/design',     $htmlPage );
    $htmlPage = preg_replace("/src=\"\/extension/", 'src="'.$hostUrlRoot.'/extension',  $htmlPage );
    $htmlPage = preg_replace("/src=\"\/var/",       'src="'.$hostUrlRoot.'/var',        $htmlPage );
    $htmlPage = preg_replace("/href=\"\//",         'href="'.$hostUrlEZ.'/',            $htmlPage );
    $htmlPage = preg_replace("/Link: \"\//",        'Link: "'.$hostUrlEZ.'/',           $htmlPage ); // proper Links in text version Change here from 285
    $htmlPage = preg_replace("/url\(\"\//",         'url("'.$hostUrlRoot.'/',           $htmlPage );
    $htmlPage = preg_replace("/url\( \"\//",        'url( "'.$hostUrlRoot.'/',          $htmlPage );
    $htmlPage = preg_replace("/url\('\//",          "url('".$hostUrlRoot.'/',           $htmlPage );
    $htmlPage = preg_replace("/url\( '\//",         "url( '".$hostUrlRoot.'/',          $htmlPage );

    return $htmlPage;// . '<!--'. print_r( $urlArray, true ).' -->';
}

function getUrlArray( $siteUrl, $currentHostName, $wwwDir )
{
    //1. case 1 host matching      www.example.com

    //2. case 2 host_uri matching  www.example.com/de

    //3. uri matching ip pased in subfolder   http://127.0.0.1/ezpublish/430/index.php/de

    $siteUrlWithoutHttp = $siteUrl;

    $UrlStartWith = 'http://';

    if ( strpos( $siteUrl, 'http://' ) === 0 )
    {
        $siteUrlWithoutHttp = substr( $siteUrl, 7, strlen( $siteUrl ) );
    }

    if ( strpos( $siteUrl, 'https://' ) === 0 )
    {
        $siteUrlWithoutHttp = substr( $siteUrl, 8, strlen( $siteUrl ) );
        $UrlStartWith = 'https://';
    }

    $siteUrlExplode = explode( '/', $siteUrlWithoutHttp );
    $siteDomainName = $siteUrlExplode[0];
    $hostName = $siteDomainName;

    $currentHostNameExplode = array_reverse( explode( '.', $currentHostName ) );
    $hostNameExplode = array_reverse( explode( '.', $siteDomainName ) );

    $hostNameAndUri = $siteUrlWithoutHttp;

    // testdomian   admin. [example.de] .jac430.fw.lokal - www. [example.de]
    if ( isset( $currentHostNameExplode[4] )
            && ( $currentHostNameExplode[4] == $hostNameExplode[1] )
                && count( $hostNameExplode ) > 1 )
    {
        $explodeHostName = explode( '/', $currentHostName );
        $domainName = $explodeHostName[0];

        $testHostName = $siteDomainName.'.'.  $currentHostNameExplode[2] .'.'. $currentHostNameExplode[1] .'.'. $currentHostNameExplode[0];
        //$hostNameAndUri = $hostName . $siteUri;
        $hostNameAndUri = str_replace( $hostName, $testHostName, $hostNameAndUri );
        $hostName = $testHostName;
    }

    $urlRoot = $hostName;
    if( $wwwDir != '' )
    {
        $urlRoot = $urlRoot .'/'. $wwwDir;
    }

    $ezUrl = $UrlStartWith . $hostNameAndUri;
    $ezRoot = $UrlStartWith . $urlRoot;

    return array( 'www_dir' => $wwwDir,
                  'site_url' => $siteUrl,
                  'current_host_name' => $currentHostName,
                  'ez_url'   => $ezUrl,
                  'ez_root'  => $ezRoot,
    //              'ez_file' => $localFileUrl
     );
}

// TODO html to text parser define in tools class

/**
 *
 * @param unknown_type $content
 * @return unknown_type
 */
function formatText( $content )
{
    $content = html_entity_decode( $content, ENT_COMPAT, 'UTF-8' );

    // end get proper links in text version
    $content = str_replace( "\r", "\n", $content );

    $serachArray = array( '&nbsp;', "</p><p>", "<p>" , '</p>', "\n\n\n\n<ol>", "</ol>\n\n\n\n", "\n\n\n\n<ul>", "</ul>\n\n\n\n", "\n\n<ol>", "</ol>\n\n", "\n\n<ul>", "</ul>\n\n", "<li>\n", '<li>', '</li>', "<hr>"     , "<br />\n", '<br />',   '<br>'    , '<h1>', '</h1>', '<h2>' , '</h2>', '<h3>'  , '</h3>' , '<h4>'   , '</h4>'  , '<h5>'    , '</h5>' );
    $replaceArray =  array( ' ', "[[[BR]]]\n", "\n"  , "\n"  , "[[[BR]]]"    , "[[[BR]]]"     , "[[[BR]]]"    , "[[[BR]]]"     , "[[[BR]]]" , "[[[BR]]]", "[[[BR]]]", "[[[BR]]]" , "<li>"  , '- '  , "\n"    , "[[[HR]]]", "[[[BR]]]", "[[[BR]]]" ,'[[[BR]]]', "\n= ", " =\n" , "\n== ", " ==\n", "\n=== ", " ===\n", "\n==== ", " ====\n", "\n===== ", " =====\n" );

    $content = str_replace( $serachArray , $replaceArray , $content );

    // get proper links in text version
    $content = formatTextLink( $content );

    $content = stripAttributes( $content );

    // preg_replace("/\n[^\w]*\n/","\n", $content);

     // turn returns to newlines:
    $content = str_replace("\r", "\n", $content);
    // turn tabs to spaces:
    $content = str_replace("\t", " ", $content);
    // next is searching for double spaces.
   /*  while (preg_match("/ /i", "$content"))
     {
     // replace them with single spaces:
     $content = str_replace(" ", " ", $content);
     }*/

    // looks for spaces after a newline:
    while ( preg_match( "/\n /", "$content" ) )
    {
        // remove that space:
        $content = str_replace( "\n ", "\n", $content );
    }


    // look for two newlines:
    while ( preg_match( "/\n\n/i", "$content" ) )
    { // turn it to one newline
         $content = str_replace( "\n\n", "\n", $content );
    }



     // the \n now separates paragraphs; change \n to <p>:
     /*$content = "<p>" . str_replace("\n", "</p><p>", $content) . "</p>";
     $content = str_replace("<p></p>", "", $content);*/
    // done!

    $serachArray = array( "\n= ",
                          "\n== ",
                          "\n=== ",
                          "\n==== ",
                          "\n===== ",
                          "\n[[[HR]]]",
                          "[[[BR]]]" );
    $replaceArray =  array( "\n\n= ",
                            "\n\n== ",
                            "\n\n=== ",
                            "\n\n==== ",
                            "\n\n===== ",
                            "\n--------------------------------------------------------------------------------",
                             "\n");

    $content = str_replace( $serachArray , $replaceArray , $content );

    return $content;
}

/**
 * strip all html tags
 * @param unknown_type $content
 * @return unknown_type
 */
function stripAttributes( $content )
{

    $pattern = array('@<script[^>]*?>.*?</script>@si',   // Strip out javascript
                    '@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
                    '@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
                    '@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments including CDATA
    );
    $content = preg_replace( $pattern, '', $content );
    return $content;
}

/**
 *
 * Format all a tags as text links
 * remove all achor tags
 * @param string $content
 * @param string $textLinkFormat here you can define how the html link ist formatted
 * the placeholders %url_link %url_text can be used in the string and will be replaced on demand
 * example: [ %url_text: %url_link ] => [ Newsletter: http://www.cjw-network.com ]
 */
function formatTextLink( $content, $textLinkFormat = "[ %url_text: %url_link ]" )
{
    //remove all ez anchors
    // <a name="eztoc598_1" id="eztoc598_1"></a> => ''
    // or <a name="bottom">my bottom anchor</a>
    $pattern = '#<a name="(.*?)".*?>(.*?)<?\/a>#is';
    $matchesAchors = '';

    //preg_match_all( $pattern, $content, $matchesAchors );
    //$content .= print_r( $matchesAchors , true );
    $content = preg_replace( $pattern, '', $content );

    // this tutorial helps me to create the regex http://www.phpmaniac.de/php_blog/php/html-seiten-crawlen-links-extrahieren/
    // find all links
    // $matches[0] => Array of original links     <a href="http://example.com" ...>This is the link text</a>
    // $matches[1] => Array with all links        http://example.com
    // $matches[2] => Array with all link texts   This is the link text
    $pattern = '/<a.*?href="(.*?)".*?>(.*?)<?\/a>/is';
 /*   $pattern = '/<a.*?(href|name)="(.*?)".*?>(.*?)<?\/a>/is';*/

    preg_match_all( $pattern, $content, $matches );
    //$content .= print_r( $matches , true );

    for( $i=0; $i<count($matches[0]); $i++ )
    {
        $completeUrlString = $matches[0][$i];
        $urlLink = $matches[1][$i];
        $urlText = $matches[2][$i];
        //  Link: "http://link">linktext
        $linkFormatted = str_replace( array( '%url_link', '%url_text' ), array( $urlLink, $urlText ), $textLinkFormat );
        //'['. $urlText .' > '. $urlLink.']'
        $content = str_replace( $completeUrlString, $linkFormatted, $content );
    }

//    $content .= print_r( $matchesAchors , true );
//    $content .= print_r( $matches , true );

    return $content;
}
}

namespace Exponential\Command\Extension\CjwNewsletter
{

class Createoutput extends \Exponential\Runnable\Command
{
    public function run()
    {
        // the script's variables were globals; functions of the script read them with "global"
        foreach ( array( 'cli', 'content', 'contentObject', 'contentType', 'currentHostName', 'htmlMailImageInclude', 'ini', 'locale', 'newsletterEditionContent', 'objectId', 'objectVersion', 'options', 'outputArray', 'outputContent', 'outputFormatId', 'script', 'siteUrl', 'skinName', 'subject', 'template', 'tpl', 'urlArray', 'user', 'userID', 'wwwDir' ) as $__name )
            ${$__name} = &$GLOBALS[$__name];
        unset( $__name );

        include_once( 'kernel/common/template.php' );

        $cli = $this->cli();
        $script = $this->script( array( 'description' => ( "CjW Newsletter CreateOutput\n\n" .

                                                                "\n" .
                                                                "createoutput.php -s siteaccess --outputFormat=0" ),
                                             'use-session' => true,
                                             'use-modules' => true,
                                             'use-extensions' => true ) );

        $options = $this->startup( "[output_format_id:][object_id:][object_version:][current_hostname:][www_dir:][skin_name:][language:]",
                                        "",
                                        array( 'output_format_id' => '--',
                                               'object_id' => '--',
                                               'object_version' => '--',
                                               'current_hostname' => '--',
                                               'www_dir' => '--',
                                               'skin_name' => '--',
                                               'language' => '--',
                                               ),
                                        false,
                                        array( 'siteaccess' => true,
                                               'user' => true )  );

        // login as admin
        // that template proecessing inlcude all images
        $user = \eZUser::fetchByName( 'admin' );

        if ( $user and $user->isEnabled( ) )
        {
            $userID = $user->attribute( 'contentobject_id' );
            \eZUser::setCurrentlyLoggedInUser( $user, $userID );
        }


        $outputFormatId = 0;
        $objectId = 0;
        $objectVersion = 0;
        $currentHostName = '#current_hostname not set#';
        $wwwDir = '';

        $skinName = 'default';


        if ( $options['output_format_id'] )
        {
            $outputFormatId = (int) $options['output_format_id'];
        }
        if ( $options['object_id'] )
        {
            $objectId = (int) $options['object_id'];
        }
        if ( $options['object_version'] )
        {
            $objectVersion = (int) $options['object_version'];
        }
        if ( $options['current_hostname'] )
        {
            $currentHostName = $options['current_hostname'];
        }
        if ( $options['www_dir'] )
        {
            $wwwDir = $options['www_dir'];
        }
        if ( $options['skin_name'] )
        {
            $skinName = $options['skin_name'];
        }

        // a skin name is a folder name, nothing else
        if ( !preg_match( '/^[a-z0-9_]{1,40}$/i', (string)$skinName ) )
        {
            $skinName = 'default';
        }

        //$iniName = $options['arguments'][0];
        $ini = \eZINI::instance( 'site.ini' );
        $siteUrl = $ini->variable( 'SiteSettings', 'SiteURL' );
        $locale = $ini->variable( 'RegionalSettings', 'Locale' );

        // 4.2.0: the output in another language of the edition (a translation); the content, the texts of the skin
        // and the dates follow it
        $renderLanguage = '';
        if ( $options['language'] && preg_match( '/^[a-z]{3}-[A-Z]{2}(@[a-z0-9]+)?$/', (string)$options['language'] ) )
        {
            $renderLanguage = (string)$options['language'];
            \CjwNewsletterRendering::switchLanguage( $renderLanguage );
            $locale = $renderLanguage;
        }

        $outputContent = '';

        // fetch objectversion
        $contentObject = \eZContentObjectVersion::fetchVersion( $objectVersion ,$objectId );

        $tpl = templateInit();
        // the version is shown in the language asked for, else in the siteaccess language when it has it (a version made
        // as a translation has that translation as its own language, and would show it by default)
        if ( is_object( $contentObject ) )
        {
            $wanted = $renderLanguage !== '' ? $renderLanguage : (string)$locale;
            if ( in_array( $wanted, (array)$contentObject->translationList( false, false ), true ) )
            {
                $contentObject->CurrentLanguage = $wanted;
            }
        }
        $tpl->setVariable('contentobject', $contentObject );

        if( !is_object( $contentObject ) )
        {
            $script->shutdown();
        }

        $contentType = 'text/html';
        $newsletterEditionContent = array( 'html' => '' , 'text' => '' );

        $htmlMailImageInclude = 0;

        $urlArray = getUrlArray( $siteUrl, $currentHostName, $wwwDir );

        // 4.2.0: the skins get the list, the language, the skin settings and absolute addresses (cjwnl_abs_url)
        \CjwNewsletterRendering::prepareTemplate( $tpl, $contentObject, $skinName, $renderLanguage !== '' ? $renderLanguage : $locale, $urlArray );
        $plainTextSkin = \CjwNewsletterRendering::skinTextFormat( $skinName ) === 'plain';


        switch( $outputFormatId )
        {
            default:
            // html 0
            case \CjwNewsletterSubscription::OUTPUT_FORMAT_HTML :
            {
                // textpart (a skin with TextFormat=plain writes the text part itself, with the plaintext views)
                $template = 'design:newsletter/skin/'.$skinName.'/outputformat/text.tpl';
                $content = $tpl->fetch( $template );
                if ( $plainTextSkin )
                {
                    $content = \CjwNewsletterPlainText::finish( $content );
                }
                else
                {
                    $content = generateAbsoluteLinks( $content, $urlArray );
                    $content = formatText( $content );
                }
                $newsletterEditionContent['text'] = $content;
                // htmlpart
                $template = 'design:newsletter/skin/'.$skinName.'/outputformat/html.tpl';
                $content = $tpl->fetch( $template );
                $content = generateAbsoluteLinks( $content, $urlArray );
                $newsletterEditionContent['html'] = $content;

                $contentType = 'multipart/alternative';

                if ( \CjwNewsletterEdition::imageIncludeIsEnabled() )
                    $htmlMailImageInclude = 1;

            } break;

            // text 1
            case \CjwNewsletterSubscription::OUTPUT_FORMAT_TEXT :
            {
                $template = 'design:newsletter/skin/'.$skinName.'/outputformat/text.tpl';
                $content = $tpl->fetch( $template );

                if ( $plainTextSkin )
                {
                    $content = \CjwNewsletterPlainText::finish( $content );
                }
                else
                {
                    $content = generateAbsoluteLinks( $content, $urlArray );
                    $content = formatText( $content );
                }

                $newsletterEditionContent['text'] = $content;
                $contentType = 'text/plain';

            } break;
            // text - html
            /*case 2:
            {
               $template = 'design:newsletter/skin/'.$skinName.'/outputformat/html_text.tpl';
               $newsletterEditionContent = $tpl->fetch( $template );
               $contentType = 'text/html';
            } break;*/

        }

        // ########## set image urls to absolut path and use CurrentHostname (testdomain)

        /*$outputContent['html'] = '';
        $outputContent['text'] = '';

        foreach( $newsletterEditionContent as $index => $outputString )
        {
            $outputContent[ $index ] = $outputString;//generateAbsoluteLinks( $outputString, $urlArray );
        }*/


        //$outputContent = $newsletterEditionContent;

        $subject = "newsletter subject $objectId";
        if ( $tpl->hasVariable( 'subject' ) )
        {
            $subject = $tpl->variable( 'subject' );
        }

        $outputArray = array( 'contentobject_id' => $objectId,
                              'contentobject_version' => $objectVersion,
                              'output_format' => $outputFormatId,
                              'content_type' => $contentType,
                              'subject' => $subject,
                              'body' => $newsletterEditionContent,
                              'template' => $template,
                              'template_validation' => $tpl->validateTemplateFile( $template, false ),
                              'template_errors' => $tpl->errorLog(),
                              'site_url' => $siteUrl,
                              'locale' => $locale,
                              'language' => $renderLanguage,
                              'skin_name' => $skinName,
                              'html_mail_image_include' => $htmlMailImageInclude
                               );

        $outputArray = array_merge( $outputArray, $urlArray );

        //serialize( $ini );
        $cli->output( serialize( $outputArray ) );
        $script->shutdown();
    }
}

}
