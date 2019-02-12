<?php
/*
 * *****************************************************************************
 * Contributions to this work were made on behalf of the GÉANT project, a 
 * project that has received funding from the European Union’s Framework 
 * Programme 7 under Grant Agreements No. 238875 (GN3) and No. 605243 (GN3plus),
 * Horizon 2020 research and innovation programme under Grant Agreements No. 
 * 691567 (GN4-1) and No. 731122 (GN4-2).
 * On behalf of the aforementioned projects, GEANT Association is the sole owner
 * of the copyright in all material which was developed by a member of the GÉANT
 * project. GÉANT Vereniging (Association) is registered with the Chamber of 
 * Commerce in Amsterdam with registration number 40535155 and operates in the 
 * UK as a branch of GÉANT Vereniging.
 * 
 * Registered office: Hoekenrode 3, 1102BR Amsterdam, The Netherlands. 
 * UK branch address: City House, 126-130 Hills Road, Cambridge CB2 1PQ, UK
 *
 * License: see the web/copyright.inc.php file in the file structure or
 *          <base_url>/copyright.php after deploying the software
 */

require_once dirname(dirname(dirname(dirname(__FILE__)))) . "/config/_config.php";

/**
 * Menu class helps to define the menu on the main page
 */
class Menu {

    /**
     * the constructor takes an array argument defining menu items.
     * the array must be indexed by strings which will be passed to user/cat_info.php a the page argument
     * the values of the array can be either a simple string which is passed to user/cat_info.php
     * as the title argument or an two element array - the first element of this array will be
     * the title and the second is a style specification applied to the given menu item
     * @param string $visibility
     * @param string $selectedLang
     */
    public function __construct($visibility = 'all', $selectedLang = '') {
        $langsArray = [];
        foreach (CONFIG['LANGUAGES'] as $lang => $value) {
            if ($lang == $selectedLang) {
                $langsArray[] = ['text'=>$value['display'], 'link'=>'javascript:changeLang("' . $lang . '")', 'class'=>'selected-lang'];
            } else {
                $langsArray[] = ['text'=>$value['display'], 'link'=>'javascript:changeLang("' . $lang . '")'];
            }
        }
        $this->menu = [['id' => 'start',
        'text' => _("Start page"), 'link' => '/',
        'visibility' => 'index'],
            ['id' => 'about',
                'text' => _("About"), 'link' => '', 'submenu' => [
                    ['text' => sprintf(_("About %s"), CONFIG['APPEARANCE']['productname']),
                        'catInfo' => ['about_cat', sprintf(_("About %s"), CONFIG['APPEARANCE']['productname'])]],
                    ['text' => sprintf(_("About %s"), CONFIG_CONFASSISTANT['CONSORTIUM']['display_name']),
                        'link' => CONFIG_CONFASSISTANT['CONSORTIUM']['homepage']],
                ]],
            ['id' => 'lang',
                'text' => _("Language"), 'submenu' => $langsArray, ],
            ['id' => 'help',
                'text' => _("Help"), 'submenu' => [
                    ['text' => _("My institution is not listed"), 'catInfo' => ['idp_not_listed', _("FAQ")], 'visibility' => 'index'],
                    ['text' => _("My device is not listed"), 'catInfo' => ['device_not_listed', _("FAQ")], 'visibility' => 'index'],
                    ['text' => core\ProfileSilverbullet::PRODUCTNAME._("Help"), 'visibility' => 'sb', 'link'=> CONFIG_CONFASSISTANT['SILVERBULLET']['documentation']],
//                    ['text' => _("What is eduroam"), 'catInfo' => ['what_is_eduroam', _("FAQ")]],
                    ['text' => _("FAQ"), 'catInfo' => ['faq', _("FAQ")]],
                    ['text' => _("Contact"), 'catInfo' => ['contact', _("FAQ")]],
//                    ['text' => _("Diagnostics"), 'link' => '/diag/diag.php'], 
//                    ['text' => _("Documentation"), 'link' => '/apidoc' ],
                ]],
            ['id' => 'manage',
                'text' => _("Manage"), 'submenu' => [
                    ['text' => sprintf(_("%s admin access"), CONFIG_CONFASSISTANT['CONSORTIUM']['display_name']),
                        'catInfo' => ['admin', sprintf(_("%s admin:<br>manage your IdP"), CONFIG_CONFASSISTANT['CONSORTIUM']['display_name'])]],
//                    ['text' => _("Become a CAT developer"),
//                        'catInfo' => ['develop', _("Become a CAT developer")]],
                ],
                'visibility' => 'index'],
//            ['id' => 'tou',
//                'text' => _("Terms of use"), 'catInfo' => ['tou', 'TOU']],
        ];
        $this->visibility = $visibility;
    }
    public function printMenu($menu = NULL, $id = NULL) {
        $menu = $menu ?? $this->menu;
        if (count($menu) == 0) {
            return;
        }
        $out = "\n<ul>\n";
        foreach ($menu as $menuItem) {
            $itemVisibility = $menuItem['visibility'] ?? 'all';
            if ($this->visibility === 'all' || $itemVisibility === 'all' || $itemVisibility === $this->visibility) {
                $iD = $menuItem['id'] ?? $id;
                $catInfo = NULL;
                if (!empty($menuItem['catInfo'])) {
                    $catInfo = 'javascript:infoCAT("' . $iD . '", "' . $menuItem['catInfo'][0] . '","' . $menuItem['catInfo'][1] . '")';
                }
                if (!empty($menuItem['link']) && substr($menuItem['link'], 0, 1) === '/') {
                    $menuItem['link'] = \core\CAT::getRootUrlPath() . $menuItem['link'];
                }
                $link = $catInfo ?? $menuItem['link'] ?? '';
                $class = empty($menuItem['class']) ? '' : ' class="' . $menuItem['class'] . '"';
                $submenu = $menuItem['submenu'] ?? [];
                $out .= $this->printMenuItem($menuItem['text'], $link, $class);
                $out .= $this->printMenu($submenu, $iD);
                $out .= "</li>\n";
            }
        }
        $out .= '</ul>';
        return($out);
    }

    private function printMenuItem($itemText, $itemLink = '', $itemClass = '') {
        
        if ($itemLink === '') {
            return("<li><span>$itemText</span>");
        }
        return "<li><a href='" . $itemLink . "'" . $itemClass . '>' . $itemText . "</a>";
    }
    
    public function printMinimalMenu() {
        $out = "\n<ul>\n";
        $out .= $this->printMenuItem(_("Start page"), \core\CAT::getRootUrlPath());
        $out .= '</ul>';
        return($out);
    }

    private $menu;
    private $visibility;
}
