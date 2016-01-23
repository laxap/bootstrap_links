<?php
namespace Laxap\BootstrapLinks\Hooks;

/*
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 */

use \TYPO3\CMS\Recordlist\LinkHandler\AbstractLinkHandler;
use \TYPO3\CMS\Recordlist\LinkHandler\LinkHandlerInterface;
use \Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Page\PageRenderer;


/**
 * Linkhandler for special types.

 * @package BootstrapLinks
 * @author Pascal Mayer <typo3@bsdist.ch>
 */
class BootstrapLinkHandler extends AbstractLinkHandler implements LinkHandlerInterface  {

	/**
	 * @var bool
	 */
	protected $updateSupported = false;

	/**
	 * Parts of the current link
	 *
	 * @var array
	 */
	protected $linkParts = [];

	/**
	 * @var array
	 */
	protected $tabs = array(array('type' => 'modal'),
							array('type' => 'popover')
							//,array('type' => 'button')
	);

	/**
	 * @var string
	 */
	protected $activeTab = 'modal';

	/**
	 * @var array
	 */
	protected $popoverPositions = array('top', 'right', 'bottom', 'left');

	/**
	 * @var array
	 */
	protected $popoverTriggers = array('hover', 'click', 'focus');

	/**
	 * @var array
	 */
	protected $buttonSizes = array('', 'btn-lg', 'btn-sm', 'btn-xs');

	/**
	 * @var array
	 */
	protected $styles = array('', 'btn-primary', 'btn-info', 'btn-warning', 'btn-danger', 'btn-default', 'btn-link');

	/**
	 * @var \TYPO3\CMS\Lang\LanguageService
	 */
	protected $lang;

	/**
	 * @var array|mixed
	 */
	protected $extConf = array();


	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		// add xlf to lang service
		$this->getLanguageService()->includeLLFile('EXT:bootstrap_links/Resources/Private/Language/locallang.xlf');

		// remove default attributes
		foreach (['target', 'rel'] as $attribute) {
			$position = array_search($attribute, $this->linkAttributes, true);
			if ($position !== false) {
				unset($this->linkAttributes[$position]);
			}
		}
	}

	/**
	 * Checks if this is the handler for the given link
	 *
	 * The handler may store this information locally for later usage.
	 *
	 * @param array $linkParts Link parts as returned from TypoLinkCodecService
	 *
	 * @return bool
	 */
	public function canHandleLink(array $linkParts) {
		if ( ! $linkParts['url']) {
			return false;
		}
		if ( substr($linkParts['url'], 0, 10) !== 'bootstrap:' ) {
			return false;
		}
		$this->linkParts = $linkParts;

		// get type from e.g. url = "bootstrap:type=popover"
		$partOne = explode(':', $linkParts['url'], 2);
		if ( isset($partOne[1]) ) {
			list($typeKey, $typeValue) = explode('=', $partOne[1], 2);

			// get type from e.g. url = "bootstrap:type=popover&desc=TestPopover&pos=right&trigger=click"
			if ( isset($linkParts['data-htmlarea-external']) && $linkParts['data-htmlarea-external'] == 1 ) {
				list($typeValue, $linkParts['params']) = explode('&', $typeValue, 2);
			}

			if( $typeKey == 'type' ) {
				$this->activeTab = $typeValue;
			}
		}

		// get parts
		if ( isset($linkParts['params']) && trim($linkParts['params']) ) {
			// remove first &, eg from "&btnurl=22"
			if (substr($linkParts['params'], 0, 1) == '&') {
				$params = substr($linkParts['params'],1);
			} else {
				$params = $linkParts['params'];
			}
			$keyValuePairs = explode('&', $params );
			if ( is_array($keyValuePairs) && count($keyValuePairs) > 0 ) {
				foreach ( $keyValuePairs as $keyValue ) {
					$param = explode('=', $keyValue, 2);
					if ( count($param) == 2 ) {
						$this->linkParts[$param[0]] = $param[1];
					}
				}
			}
		}

		return true;
	}

	/**
	 * Format the current link for HTML output
	 *
	 * @return string
	 */
	public function formatCurrentUrl() {
		return $this->linkParts['url'];
	}

	/**
	 * Render the link handler
	 *
	 * @param ServerRequestInterface $request
	 *
	 * @return string
	 */
	public function render(ServerRequestInterface $request) {
		// get lang service
		$this->lang = $this->getLanguageService();

		// js for form submit handling
		GeneralUtility::makeInstance(PageRenderer::class)->loadRequireJsModule('/typo3conf/ext/bootstrap_links/Resources/Public/JavaScript/BootstrapLinkHandler.js');

		$bsLinkForm = '';
		// get tab nav
		$bsLinkForm .= $this->getTabNav();
		// get tab contents
		$bsLinkForm .= '<div class="tab-content">';
		foreach ( $this->tabs as $tab ) {
			$bsLinkForm .= $this->getTabContent($tab);
		}
		$bsLinkForm .= '</div>';

		return $bsLinkForm;
	}

	/**
	 * @return string[] Array of body-tag attributes
	 */
	public function getBodyTagAttributes() {
		return [];
	}


	/** @cond
	 * --- internal methods ---
	 * @endcond
	 */

	/**
	 * @return string
	 */
	protected function getTabNav() {
		$tabNav = '';
		foreach ( $this->tabs  as $tab ) {
			// active tab
			if ( $tab['type'] == $this->activeTab ) {
				$attribs = ' class="active"';
			} else {
				$attribs = '';
			}
			// tab link
			$tabNav .= '<li ' . $attribs . '><a href="#bslink' . $tab['type'] . '" aria-controls="bslink' . $tab['type'] . '" role="tab" data-toggle="tab">';
			$tabNav .= $this->lang->getLL('browselinks.linkType.I.' . $tab['type']) . '</a></li>';
		}
		$tabNav = '<ul class="nav nav-tabs" role="tablist" style="margin-top: 10px;">' . $tabNav . '</ul>';

		return $tabNav;
	}

	/**
	 * @param array $tab
	 * @return string
	 */
	protected function getTabContent($tab) {
		$tabContent = '';
		switch ( $tab['type']) {
			case 'modal':
				$tabContent = $this->getTabContentWrapped('modal', '<form action="" id="lbslinkmodalform">' . $this->getModalTabContent() . '</form>');
				break;
			case 'popover':
				$tabContent = $this->getTabContentWrapped('popover', '<form action="" id="lbslinkpopoverform">' . $this->getPopoverTabContent() . '</form>');
				break;
			case 'button':
				$tabContent = $this->getTabContentWrapped('button', '<form action="" id="lbslinkbuttonform">' . $this->getButtonTabContent() . '</form>');
				break;
		}
		return $tabContent;
	}

	/**
	 * @return string
	 */
	protected function getModalTabContent() {
		$content = '<tr><td style="width: 96px;">' . $this->lang->getLL('browselinks.modalid') . '</td>
						<td>
							<input type="text" name="lurl" size="30" value="' . htmlspecialchars(!empty($this->linkParts['value']) ? $this->linkParts['value'] : '') . '" />
							<input class="btn btn-default" type="submit" value="' . $this->getLanguageService()->getLL('setLink', true) . '" />
						</td>
					</tr>';

		// default fields
		//$content .= $this->getDefaultFields();

		return '<table border="0" cellpadding="2" cellspacing="1">' . $content . '</table>';
	}

	/**
	 * @return string
	 */
	protected function getPopoverTabContent() {
		$content = '<tr><td style="width: 96px;">' . $this->lang->getLL('browselinks.desc') . '</td>
						<td>
							<input type="text" name="lpopoverdesc" size="30" value="' . urldecode(!empty($this->linkParts['desc']) ? $this->linkParts['desc'] : '') . '" />
							<input class="btn btn-default" type="submit" value="' . $this->getLanguageService()->getLL('setLink', true) . '" />
						</td>
					</tr>';

		// position
		$content .= '<tr><td>' . $this->lang->getLL('browselinks.position') . '</td><td><select name="lpopoverpos">';
		foreach ( $this->popoverPositions as $pos ) {
			if ( $this->linkParts['pos'] == $pos) {
				$selected = ' selected="selected"';
			} else {
				$selected = '';
			}
			$content .= '<option ' . $selected . '>' . $pos . '</option>';
		}
		$content .= '</select></td></tr>';

		// trigger
		$content .= '<tr><td>' . $this->lang->getLL('browselinks.trigger') . '</td><td><select name="lpopovertrigger">';
		foreach ( $this->popoverTriggers as $trigger ) {
			if ( $this->linkParts['trigger'] == $trigger) {
				$selected = ' selected="selected"';
			} else {
				$selected = '';
			}
			$content .= '<option ' . $selected . '>' . $trigger . '</option>';
		}
		$content .= '</select></td></tr>';

		// default fields
		//$content .= $this->getDefaultFields();

		return '<table border="0" cellpadding="2" cellspacing="1">' . $content . '</table>';
	}

	/**
	 * @return string
	 */
	protected function getButtonTabContent() {
		$content = '<tr><td style="width: 96px;">' . $this->lang->getLL('browselinks.urloruid') . '</td>
						<td>
							<input type="text" name="lurl" size="30" value="' . urldecode(!empty($this->linkParts['btnurl']) ? $this->linkParts['btnurl'] : '') . '" />
							<input class="btn btn-default" type="submit" value="' . $this->getLanguageService()->getLL('setLink', true) . '" />
						</td>
					</tr>';

		// default fields
		//$content .= $this->getDefaultFields();

		return '<table border="0" cellpadding="2" cellspacing="1">' . $content . '</table>';
	}

	/**
	 * @param string $type
	 * @param string $innerHtml
	 * @return string
	 */
	protected function getTabContentWrapped($type, $innerHtml) {
		if ( $type == $this->activeTab ) {
			$addOnClass = 'active';
		} else {
			$addOnClass = '';
		}
		return '<div role="tabpanel" class="tab-pane ' . $addOnClass . '" id="bslink' . $type . '">' . $innerHtml . '</div>';
	}

	/**
	 * @return string
	 */
	protected function getDefaultFields() {
		$content = '<tr><td colspan="2"><h4>' . $this->lang->getLL('browselinks.linkstyle') . '</h4></td></tr>';
		$content .= '<tr><td style="width: 96px;">' . $this->lang->getLL('browselinks.title') . '</td>';
		$content .= '<td><input type="text" name="ltitle" size="30" value="' . urldecode(!empty($this->linkParts['title']) ? $this->linkParts['title'] : '') . '" /></td></tr>';

		$content .= '<tr><td style="width: 96px;">' . $this->lang->getLL('browselinks.style') . '</td>';
		$content .= '<td><select name="lstyle">';
		foreach ( $this->styles as $index => $style ) {
			( $this->linkParts['style'] == $style) ? $selected = ' selected="selected"' : $selected = '';
			$content .= '<option value="' . $style . '" ' . $selected . '>' . $this->lang->getLL('browselinks.style.I.' . $index) . '</option>';
		}
		$content .= '</select></td></tr>';

		$content .= '<tr><td style="width: 96px;">' . $this->lang->getLL('browselinks.size') . '</td>';
		$content .= '<td><select name="lbtnsize">';
		foreach ( $this->buttonSizes as $index => $sizeClass ) {
			( $this->linkParts['btnsize'] == $sizeClass) ? $selected = ' selected="selected"' : $selected = '';
			$content .= '<option value="' . $sizeClass . '" ' . $selected . '>' . $this->lang->getLL('browselinks.btnsize.I.' . $index) . '</option>';
		}
		$content .= '</select> ' . $this->lang->getLL('browselinks.onlyForButtons') .  '</td></tr>';
		return $content;
	}
}


?>