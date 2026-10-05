<?php
/**
 * cjw newsletter Operator autoloading
 *
 * @copyright Copyright (C) 2007-2012 CJW Network - Coolscreen.de, JAC Systeme GmbH, Webmanufaktur. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag// | $Id: $
 * @package cjw_newsletter
 * @subpackage operators
 * @filesource
 */

$eZTemplateOperatorArray = array();

// $text|cjw_newsletter_preg_replace( $search_string, $replace_string )
$eZTemplateOperatorArray[] = array( 'script' => 'extension/cjw_newsletter/autoloads/cjwnewsletteroperators.php',
                                    'class' => 'CjwNewsletterOperators',
                                    'operator_names' => array( 'cjw_newsletter_preg_replace',
                                                               'cjw_newsletter_str_replace',
                                                               'cjw_newsletter_variable' ) );

// ---- 4.2.0 N3 Rendering operators (area N3 changes only this block)
// conditions of the "newsletter condition" tag, plain text of rich text, the interests block, the data of the rendering pages
$eZTemplateOperatorArray[] = array( 'script' => 'extension/cjw_newsletter/classes/rendering/cjwnewsletterrenderingoperators.php',
                                    'class' => 'CjwNewsletterRenderingOperators',
                                    'operator_names' => array( 'cjwnl_condition_open', 'cjwnl_condition_else', 'cjwnl_condition_close',
                                                               'cjwnl_plaintext', 'cjwnl_richtext', 'cjwnl_interests_block',
                                                               'cjwnl_abs_url', 'cjwnl_text_underline', 'cjwnl_text_wrap',
                                                               'cjwnl_rendering_data' ) );
// ---- end N3 operators

?>
