<?php
// This file is part of Ranking block for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Theme iprimed block settings file
 *
 * @package    theme_iprimed
 * @copyright  2017 Willian Mano http://conecti.me
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This line protects the file from being accessed by a URL directly.
defined('MOODLE_INTERNAL') || die();

// This is used for performance, we don't need to know about these settings on every page in Moodle, only when
// we are looking at the admin settings pages.
if ($ADMIN->fulltree) {

    // Boost provides a nice setting page which splits settings onto separate tabs. We want to use it here.
    $settings = new theme_boost_admin_settingspage_tabs('themesettingiprimed', get_string('configtitle', 'theme_iprimed'));

    /*
    * ----------------------
    * General settings tab
    * ----------------------
    */
    $page = new admin_settingpage('theme_iprimed_general', get_string('generalsettings', 'theme_iprimed'));

    // Logo file setting.
    $name = 'theme_iprimed/logo';
    $title = get_string('logo', 'theme_iprimed');
    $description = get_string('logodesc', 'theme_iprimed');
    $opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.tiff', '.svg'), 'maxfiles' => 1);
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'logo', 0, $opts);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Favicon setting.
    $name = 'theme_iprimed/favicon';
    $title = get_string('favicon', 'theme_iprimed');
    $description = get_string('favicondesc', 'theme_iprimed');
    $opts = array('accepted_types' => array('.ico'), 'maxfiles' => 1);
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'favicon', 0, $opts);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Preset.
    $name = 'theme_iprimed/preset';
    $title = get_string('preset', 'theme_iprimed');
    $description = get_string('preset_desc', 'theme_iprimed');
    $default = 'default.scss';

    $context = context_system::instance();
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'theme_iprimed', 'preset', 0, 'itemid, filepath, filename', false);

    $choices = [];
    foreach ($files as $file) {
        $choices[$file->get_filename()] = $file->get_filename();
    }
    // These are the built in presets.
    $choices['default.scss'] = 'default.scss';
    $choices['plain.scss'] = 'plain.scss';

    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Preset files setting.
    $name = 'theme_iprimed/presetfiles';
    $title = get_string('presetfiles', 'theme_iprimed');
    $description = get_string('presetfiles_desc', 'theme_iprimed');

    $setting = new admin_setting_configstoredfile($name, $title, $description, 'preset', 0,
        array('maxfiles' => 20, 'accepted_types' => array('.scss')));
    $page->add($setting);

    // Login page background image.
    $name = 'theme_iprimed/loginbgimg';
    $title = get_string('loginbgimg', 'theme_iprimed');
    $description = get_string('loginbgimg_desc', 'theme_iprimed');
    $opts = array('accepted_types' => array('.png', '.jpg', '.svg'));
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'loginbgimg', 0, $opts);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Variable $brand-color.
    // We use an empty default value because the default colour should come from the preset.
    $name = 'theme_iprimed/brandcolor';
    $title = get_string('brandcolor', 'theme_iprimed');
    $description = get_string('brandcolor_desc', 'theme_iprimed');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Variable $navbar-header-color.
    // We use an empty default value because the default colour should come from the preset.
    $name = 'theme_iprimed/navbarheadercolor';
    $title = get_string('navbarheadercolor', 'theme_iprimed');
    $description = get_string('navbarheadercolor_desc', 'theme_iprimed');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Variable $navbar-bg.
    // We use an empty default value because the default colour should come from the preset.
    $name = 'theme_iprimed/navbarbg';
    $title = get_string('navbarbg', 'theme_iprimed');
    $description = get_string('navbarbg_desc', 'theme_iprimed');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Variable $navbar-bg-hover.
    // We use an empty default value because the default colour should come from the preset.
    $name = 'theme_iprimed/navbarbghover';
    $title = get_string('navbarbghover', 'theme_iprimed');
    $description = get_string('navbarbghover_desc', 'theme_iprimed');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Course format option.
    $name = 'theme_iprimed/coursepresentation';
    $title = get_string('coursepresentation', 'theme_iprimed');
    $description = get_string('coursepresentationdesc', 'theme_iprimed');
    $options = [];
    $options[1] = get_string('coursedefault', 'theme_iprimed');
    $options[2] = get_string('coursecover', 'theme_iprimed');
    $setting = new admin_setting_configselect($name, $title, $description, $default, $options);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $name = 'theme_iprimed/courselistview';
    $title = get_string('courselistview', 'theme_iprimed');
    $description = get_string('courselistviewdesc', 'theme_iprimed');
    $setting = new admin_setting_configcheckbox($name, $title, $description, 0);
    $page->add($setting);

    // Must add the page after definiting all the settings!
    $settings->add($page);

    /*
    * ----------------------
    * Advanced settings tab
    * ----------------------
    */
    $page = new admin_settingpage('theme_iprimed_advanced', get_string('advancedsettings', 'theme_iprimed'));

    // Raw SCSS to include before the content.
    $setting = new admin_setting_scsscode('theme_iprimed/scsspre',
        get_string('rawscsspre', 'theme_iprimed'), get_string('rawscsspre_desc', 'theme_iprimed'), '', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Raw SCSS to include after the content.
    $setting = new admin_setting_scsscode('theme_iprimed/scss', get_string('rawscss', 'theme_iprimed'),
        get_string('rawscss_desc', 'theme_iprimed'), '', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Google analytics block.
    $name = 'theme_iprimed/googleanalytics';
    $title = get_string('googleanalytics', 'theme_iprimed');
    $description = get_string('googleanalyticsdesc', 'theme_iprimed');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);

    /*
    * -----------------------
    * Frontpage settings tab
    * -----------------------
    */
    $page = new admin_settingpage('theme_iprimed_frontpage', get_string('frontpagesettings', 'theme_iprimed'));

    // Headerimg file setting.
    $name = 'theme_iprimed/headerimg';
    $title = get_string('headerimg', 'theme_iprimed');
    $description = get_string('headerimgdesc', 'theme_iprimed');
    $opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.tiff', '.svg'));
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'headerimg', 0, $opts);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Bannerheading.
    $name = 'theme_iprimed/bannerheading';
    $title = get_string('bannerheading', 'theme_iprimed');
    $description = get_string('bannerheadingdesc', 'theme_iprimed');
    $default = 'Perfect Learning System';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Bannercontent.
    $name = 'theme_iprimed/bannercontent';
    $title = get_string('bannercontent', 'theme_iprimed');
    $description = get_string('bannercontentdesc', 'theme_iprimed');
    $default = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.';
    $setting = new admin_setting_configtextarea($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $name = 'theme_iprimed/displaymarketingbox';
    $title = get_string('displaymarketingbox', 'theme_iprimed');
    $description = get_string('displaymarketingboxdesc', 'theme_iprimed');
    $default = 1;
    $choices = array(0 => 'No', 1 => 'Yes');
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $page->add($setting);

    // Marketing1icon.
    $name = 'theme_iprimed/marketing1icon';
    $title = get_string('marketing1icon', 'theme_iprimed');
    $description = get_string('marketing1icondesc', 'theme_iprimed');
    $opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.tiff', '.svg'));
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'marketing1icon', 0, $opts);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Marketing1heading.
    $name = 'theme_iprimed/marketing1heading';
    $title = get_string('marketing1heading', 'theme_iprimed');
    $description = get_string('marketing1headingdesc', 'theme_iprimed');
    $default = 'We host';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Marketing1subheading.
    $name = 'theme_iprimed/marketing1subheading';
    $title = get_string('marketing1subheading', 'theme_iprimed');
    $description = get_string('marketing1subheadingdesc', 'theme_iprimed');
    $default = 'your MOODLE';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Marketing1content.
    $name = 'theme_iprimed/marketing1content';
    $title = get_string('marketing1content', 'theme_iprimed');
    $description = get_string('marketing1contentdesc', 'theme_iprimed');
    $default = 'Moodle hosting in a powerful cloud infrastructure';
    $setting = new admin_setting_configtextarea($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Marketing1url.
    $name = 'theme_iprimed/marketing1url';
    $title = get_string('marketing1url', 'theme_iprimed');
    $description = get_string('marketing1urldesc', 'theme_iprimed');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Marketing2icon.
    $name = 'theme_iprimed/marketing2icon';
    $title = get_string('marketing2icon', 'theme_iprimed');
    $description = get_string('marketing2icondesc', 'theme_iprimed');
    $opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.tiff', '.svg'));
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'marketing2icon', 0, $opts);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Marketing2heading.
    $name = 'theme_iprimed/marketing2heading';
    $title = get_string('marketing2heading', 'theme_iprimed');
    $description = get_string('marketing2headingdesc', 'theme_iprimed');
    $default = 'Consulting';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Marketing2subheading.
    $name = 'theme_iprimed/marketing2subheading';
    $title = get_string('marketing2subheading', 'theme_iprimed');
    $description = get_string('marketing2subheadingdesc', 'theme_iprimed');
    $default = 'for your company';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Marketing2content.
    $name = 'theme_iprimed/marketing2content';
    $title = get_string('marketing2content', 'theme_iprimed');
    $description = get_string('marketing2contentdesc', 'theme_iprimed');
    $default = 'Moodle consulting and training for you';
    $setting = new admin_setting_configtextarea($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Marketing2url.
    $name = 'theme_iprimed/marketing2url';
    $title = get_string('marketing2url', 'theme_iprimed');
    $description = get_string('marketing2urldesc', 'theme_iprimed');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Marketing3icon.
    $name = 'theme_iprimed/marketing3icon';
    $title = get_string('marketing3icon', 'theme_iprimed');
    $description = get_string('marketing3icondesc', 'theme_iprimed');
    $opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.tiff', '.svg'));
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'marketing3icon', 0, $opts);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Marketing3heading.
    $name = 'theme_iprimed/marketing3heading';
    $title = get_string('marketing3heading', 'theme_iprimed');
    $description = get_string('marketing3headingdesc', 'theme_iprimed');
    $default = 'Development';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Marketing3subheading.
    $name = 'theme_iprimed/marketing3subheading';
    $title = get_string('marketing3subheading', 'theme_iprimed');
    $description = get_string('marketing3subheadingdesc', 'theme_iprimed');
    $default = 'themes and plugins';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Marketing3content.
    $name = 'theme_iprimed/marketing3content';
    $title = get_string('marketing3content', 'theme_iprimed');
    $description = get_string('marketing3contentdesc', 'theme_iprimed');
    $default = 'We develop themes and plugins as your desires';
    $setting = new admin_setting_configtextarea($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Marketing3url.
    $name = 'theme_iprimed/marketing3url';
    $title = get_string('marketing3url', 'theme_iprimed');
    $description = get_string('marketing3urldesc', 'theme_iprimed');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Marketing4icon.
    $name = 'theme_iprimed/marketing4icon';
    $title = get_string('marketing4icon', 'theme_iprimed');
    $description = get_string('marketing4icondesc', 'theme_iprimed');
    $opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.tiff', '.svg'));
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'marketing4icon', 0, $opts);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Marketing4heading.
    $name = 'theme_iprimed/marketing4heading';
    $title = get_string('marketing4heading', 'theme_iprimed');
    $description = get_string('marketing4headingdesc', 'theme_iprimed');
    $default = 'Support';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Marketing4subheading.
    $name = 'theme_iprimed/marketing4subheading';
    $title = get_string('marketing4subheading', 'theme_iprimed');
    $description = get_string('marketing4subheadingdesc', 'theme_iprimed');
    $default = 'we give you answers';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Marketing4content.
    $name = 'theme_iprimed/marketing4content';
    $title = get_string('marketing4content', 'theme_iprimed');
    $description = get_string('marketing4contentdesc', 'theme_iprimed');
    $default = 'MOODLE specialized support';
    $setting = new admin_setting_configtextarea($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Marketing4url.
    $name = 'theme_iprimed/marketing4url';
    $title = get_string('marketing4url', 'theme_iprimed');
    $description = get_string('marketing4urldesc', 'theme_iprimed');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);


    // Marketing5icon.
    $name = 'theme_iprimed/marketing5icon';
    $title = get_string('marketing5icon', 'theme_iprimed');
    $description = get_string('marketing5icondesc', 'theme_iprimed');
    $opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.tiff', '.svg'));
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'marketing5icon', 0, $opts);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Marketing5heading.
    $name = 'theme_iprimed/marketing5heading';
    $title = get_string('marketing5heading', 'theme_iprimed');
    $description = get_string('marketing5headingdesc', 'theme_iprimed');
    $default = 'Consulting';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Marketing5subheading.
    $name = 'theme_iprimed/marketing5subheading';
    $title = get_string('marketing5subheading', 'theme_iprimed');
    $description = get_string('marketing5subheadingdesc', 'theme_iprimed');
    $default = 'for your company';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Marketing5content.
    $name = 'theme_iprimed/marketing5content';
    $title = get_string('marketing5content', 'theme_iprimed');
    $description = get_string('marketing5contentdesc', 'theme_iprimed');
    $default = 'Moodle consulting and training for you';
    $setting = new admin_setting_configtextarea($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Marketing5url.
    $name = 'theme_iprimed/marketing5url';
    $title = get_string('marketing5url', 'theme_iprimed');
    $description = get_string('marketing5urldesc', 'theme_iprimed');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);




    // Enable or disable Slideshow settings.
    $name = 'theme_iprimed/sliderenabled';
    $title = get_string('sliderenabled', 'theme_iprimed');
    $description = get_string('sliderenableddesc', 'theme_iprimed');
    $setting = new admin_setting_configcheckbox($name, $title, $description, 0);
    $page->add($setting);

    // Enable slideshow on frontpage guest page.
    $name = 'theme_iprimed/sliderfrontpage';
    $title = get_string('sliderfrontpage', 'theme_iprimed');
    $description = get_string('sliderfrontpagedesc', 'theme_iprimed');
    $default = 0;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default);
    $page->add($setting);

    $name = 'theme_iprimed/slidercount';
    $title = get_string('slidercount', 'theme_iprimed');
    $description = get_string('slidercountdesc', 'theme_iprimed');
    $default = 1;
    $options = array();
    for ($i = 0; $i < 13; $i++) {
        $options[$i] = $i;
    }
    $setting = new admin_setting_configselect($name, $title, $description, $default, $options);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // If we don't have an slide yet, default to the preset.
    $slidercount = get_config('theme_iprimed', 'slidercount');

    if (!$slidercount) {
        $slidercount = 1;
    }

    for ($sliderindex = 1; $sliderindex <= $slidercount; $sliderindex++) {
        $fileid = 'sliderimage' . $sliderindex;
        $name = 'theme_iprimed/sliderimage' . $sliderindex;
        $title = get_string('sliderimage', 'theme_iprimed');
        $description = get_string('sliderimagedesc', 'theme_iprimed');
        $opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.tiff', '.svg'), 'maxfiles' => 1);
        $setting = new admin_setting_configstoredfile($name, $title, $description, $fileid, 0, $opts);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

        $name = 'theme_iprimed/slidertitle' . $sliderindex;
        $title = get_string('slidertitle', 'theme_iprimed');
        $description = get_string('slidertitledesc', 'theme_iprimed');
        $setting = new admin_setting_configtext($name, $title, $description, '', PARAM_TEXT);
        $page->add($setting);

        $name = 'theme_iprimed/slidercap' . $sliderindex;
        $title = get_string('slidercaption', 'theme_iprimed');
        $description = get_string('slidercaptiondesc', 'theme_iprimed');
        $default = '';
        $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
        $page->add($setting);
    }

    // Enable or disable Slideshow settings.
    $name = 'theme_iprimed/numbersfrontpage';
    $title = get_string('numbersfrontpage', 'theme_iprimed');
    $description = get_string('numbersfrontpagedesc', 'theme_iprimed');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $page->add($setting);

    // Enable sponsors on frontpage guest page.
    $name = 'theme_iprimed/sponsorsfrontpage';
    $title = get_string('sponsorsfrontpage', 'theme_iprimed');
    $description = get_string('sponsorsfrontpagedesc', 'theme_iprimed');
    $default = 0;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default);
    $page->add($setting);

    $name = 'theme_iprimed/sponsorstitle';
    $title = get_string('sponsorstitle', 'theme_iprimed');
    $description = get_string('sponsorstitledesc', 'theme_iprimed');
    $default = get_string('sponsorstitledefault', 'theme_iprimed');
    $setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_TEXT);
    $page->add($setting);

    $name = 'theme_iprimed/sponsorssubtitle';
    $title = get_string('sponsorssubtitle', 'theme_iprimed');
    $description = get_string('sponsorssubtitledesc', 'theme_iprimed');
    $default = get_string('sponsorssubtitledefault', 'theme_iprimed');
    $setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_TEXT);
    $page->add($setting);

    $name = 'theme_iprimed/sponsorscount';
    $title = get_string('sponsorscount', 'theme_iprimed');
    $description = get_string('sponsorscountdesc', 'theme_iprimed');
    $default = 1;
    $options = array();
    for ($i = 0; $i < 5; $i++) {
        $options[$i] = $i;
    }
    $setting = new admin_setting_configselect($name, $title, $description, $default, $options);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // If we don't have an slide yet, default to the preset.
    $sponsorscount = get_config('theme_iprimed', 'sponsorscount');

    if (!$sponsorscount) {
        $sponsorscount = 1;
    }

    for ($sponsorsindex = 1; $sponsorsindex <= $sponsorscount; $sponsorsindex++) {
        $fileid = 'sponsorsimage' . $sponsorsindex;
        $name = 'theme_iprimed/sponsorsimage' . $sponsorsindex;
        $title = get_string('sponsorsimage', 'theme_iprimed');
        $description = get_string('sponsorsimagedesc', 'theme_iprimed');
        $opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.tiff', '.svg'), 'maxfiles' => 1);
        $setting = new admin_setting_configstoredfile($name, $title, $description, $fileid, 0, $opts);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

        $name = 'theme_iprimed/sponsorsurl' . $sponsorsindex;
        $title = get_string('sponsorsurl', 'theme_iprimed');
        $description = get_string('sponsorsurldesc', 'theme_iprimed');
        $setting = new admin_setting_configtext($name, $title, $description, '', PARAM_TEXT);
        $page->add($setting);
    }

    // Enable clients on frontpage guest page.
    $name = 'theme_iprimed/clientsfrontpage';
    $title = get_string('clientsfrontpage', 'theme_iprimed');
    $description = get_string('clientsfrontpagedesc', 'theme_iprimed');
    $default = 0;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default);
    $page->add($setting);

    $name = 'theme_iprimed/clientstitle';
    $title = get_string('clientstitle', 'theme_iprimed');
    $description = get_string('clientstitledesc', 'theme_iprimed');
    $default = get_string('clientstitledefault', 'theme_iprimed');
    $setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_TEXT);
    $page->add($setting);

    $name = 'theme_iprimed/clientssubtitle';
    $title = get_string('clientssubtitle', 'theme_iprimed');
    $description = get_string('clientssubtitledesc', 'theme_iprimed');
    $default = get_string('clientssubtitledefault', 'theme_iprimed');
    $setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_TEXT);
    $page->add($setting);

    $name = 'theme_iprimed/clientscount';
    $title = get_string('clientscount', 'theme_iprimed');
    $description = get_string('clientscountdesc', 'theme_iprimed');
    $default = 1;
    $options = array();
    for ($i = 0; $i < 11; $i++) {
        $options[$i] = $i;
    }
    $setting = new admin_setting_configselect($name, $title, $description, $default, $options);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // If we don't have an slide yet, default to the preset.
    $clientscount = get_config('theme_iprimed', 'clientscount');

    if (!$clientscount) {
        $clientscount = 1;
    }

    for ($clientsindex = 1; $clientsindex <= $clientscount; $clientsindex++) {
        $fileid = 'clientsimage' . $clientsindex;
        $name = 'theme_iprimed/clientsimage' . $clientsindex;
        $title = get_string('clientsimage', 'theme_iprimed');
        $description = get_string('clientsimagedesc', 'theme_iprimed');
        $opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.tiff', '.svg'), 'maxfiles' => 1);
        $setting = new admin_setting_configstoredfile($name, $title, $description, $fileid, 0, $opts);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

        $name = 'theme_iprimed/clientsurl' . $clientsindex;
        $title = get_string('clientsurl', 'theme_iprimed');
        $description = get_string('clientsurldesc', 'theme_iprimed');
        $setting = new admin_setting_configtext($name, $title, $description, '', PARAM_TEXT);
        $page->add($setting);
    }

    $settings->add($page);

    /*
    * --------------------
    * Footer settings tab
    * --------------------
    */
    $page = new admin_settingpage('theme_iprimed_footer', get_string('footersettings', 'theme_iprimed'));

    $name = 'theme_iprimed/getintouchcontent';
    $title = get_string('getintouchcontent', 'theme_iprimed');
    $description = get_string('getintouchcontentdesc', 'theme_iprimed');
    $default = 'Conecti.me';
    $setting = new admin_setting_configtextarea($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Website.
    $name = 'theme_iprimed/website';
    $title = get_string('website', 'theme_iprimed');
    $description = get_string('websitedesc', 'theme_iprimed');
    $default = 'http://conecti.me';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Mobile.
    $name = 'theme_iprimed/mobile';
    $title = get_string('mobile', 'theme_iprimed');
    $description = get_string('mobiledesc', 'theme_iprimed');
    $default = 'Mobile : +55 (98) 00123-45678';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Mail.
    $name = 'theme_iprimed/mail';
    $title = get_string('mail', 'theme_iprimed');
    $description = get_string('maildesc', 'theme_iprimed');
    $default = 'willianmano@conectime.com';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Facebook url setting.
    $name = 'theme_iprimed/facebook';
    $title = get_string('facebook', 'theme_iprimed');
    $description = get_string('facebookdesc', 'theme_iprimed');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Twitter url setting.
    $name = 'theme_iprimed/twitter';
    $title = get_string('twitter', 'theme_iprimed');
    $description = get_string('twitterdesc', 'theme_iprimed');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Googleplus url setting.
    $name = 'theme_iprimed/googleplus';
    $title = get_string('googleplus', 'theme_iprimed');
    $description = get_string('googleplusdesc', 'theme_iprimed');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Linkdin url setting.
    $name = 'theme_iprimed/linkedin';
    $title = get_string('linkedin', 'theme_iprimed');
    $description = get_string('linkedindesc', 'theme_iprimed');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Youtube url setting.
    $name = 'theme_iprimed/youtube';
    $title = get_string('youtube', 'theme_iprimed');
    $description = get_string('youtubedesc', 'theme_iprimed');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Instagram url setting.
    $name = 'theme_iprimed/instagram';
    $title = get_string('instagram', 'theme_iprimed');
    $description = get_string('instagramdesc', 'theme_iprimed');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Top footer background image.
    $name = 'theme_iprimed/topfooterimg';
    $title = get_string('topfooterimg', 'theme_iprimed');
    $description = get_string('topfooterimgdesc', 'theme_iprimed');
    $opts = array('accepted_types' => array('.png', '.jpg', '.svg'));
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'topfooterimg', 0, $opts);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Footer Background Color 
    $name = 'theme_iprimed/footercolor';
    $title = get_string('footercolor', 'theme_iprimed');
    $description = get_string('footercolor_desc', 'theme_iprimed');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Footer right 1 Icon
    $name = 'theme_iprimed/footer1icon';
    $title = get_string('footer1icon', 'theme_iprimed');
    $description = get_string('footer1icon_decs', 'theme_iprimed');
    $opts = array('accepted_types' => array('.png', '.jpg', '.svg'));
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'footer1icon', 0, $opts);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Footer right 2 Icon
    $name = 'theme_iprimed/footer2icon';
    $title = get_string('footer2icon', 'theme_iprimed');
    $description = get_string('footer2icon_decs', 'theme_iprimed');
    $opts = array('accepted_types' => array('.png', '.jpg', '.svg'));
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'footer2icon', 0, $opts);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Footer right 3 Icon
    $name = 'theme_iprimed/footer3icon';
    $title = get_string('footer3icon', 'theme_iprimed');
    $description = get_string('footer3icon_decs', 'theme_iprimed');
    $opts = array('accepted_types' => array('.png', '.jpg', '.svg'));
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'footer3icon', 0, $opts);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);


    // Footer left 1 Icon
    $name = 'theme_iprimed/footer1lefticon';
    $title = get_string('footer1lefticon', 'theme_iprimed');
    $description = get_string('footer1lefticon_decs', 'theme_iprimed');
    $opts = array('accepted_types' => array('.png', '.jpg', '.svg'));
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'footer1lefticon', 0, $opts);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);

    // Forum page.
    $settingpage = new admin_settingpage('theme_iprimed_forum', get_string('forumsettings', 'theme_iprimed'));

    $settingpage->add(new admin_setting_heading('theme_iprimed_forumheading', null,
            format_text(get_string('forumsettingsdesc', 'theme_iprimed'), FORMAT_MARKDOWN)));

    // Enable custom template.
    $name = 'theme_iprimed/forumcustomtemplate';
    $title = get_string('forumcustomtemplate', 'theme_iprimed');
    $description = get_string('forumcustomtemplatedesc', 'theme_iprimed');
    $default = 0;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default);
    $settingpage->add($setting);

    // Header setting.
    $name = 'theme_iprimed/forumhtmlemailheader';
    $title = get_string('forumhtmlemailheader', 'theme_iprimed');
    $description = get_string('forumhtmlemailheaderdesc', 'theme_iprimed');
    $default = '';
    $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
    $settingpage->add($setting);

    // Footer setting.
    $name = 'theme_iprimed/forumhtmlemailfooter';
    $title = get_string('forumhtmlemailfooter', 'theme_iprimed');
    $description = get_string('forumhtmlemailfooterdesc', 'theme_iprimed');
    $default = '';
    $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
    $settingpage->add($setting);

    $settings->add($settingpage);
}
