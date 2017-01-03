<?php
/**
 * @package     CedDisqusComments
 * @subpackage  com_ceddisquscomments
 * http://confluence.galaxiis.com/display/GAL/SOFTWARE+LICENSE+AGREEMENT</license>
 * @copyright   Copyright (C) 2013-2016 galaxiis.com All rights reserved.
 * @license     The author and holder of the copyright of the software is Cédric Walter. The licensor and as such issuer of the license and bearer of the
 *              worldwide exclusive usage rights including the rights to reproduce, distribute and make the software available to the public
 *              in any form is Galaxiis.com
 *              see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

class plgContentCedDisqusComments extends JPlugin
{
    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);
        $this->loadLanguage();
    }

    public function onContentPrepare($context, &$row, &$params, $page = 0)
    {
        //Do not run in admin area and non HTML  (rss, json, error)
        $app = JFactory::getApplication();
        if ($app->isAdmin() || JFactory::getDocument()->getType() !== 'html') {
            return true;
        }

        $print = JFactory::getApplication()->input->get('print') == 1;
        $isActive = JFactory::getApplication()->input->getWord('view') == 'article' && $context == 'com_content.article' && !$print;
        if ($isActive) {
            $this->addWidget($row, true);
        }

        return true;
    }

    public function onContentAfterDisplay($context, &$row, &$params, $page = 0)
    {
        //Do not run in admin area
        $app = JFactory::getApplication();
        if ($app->isAdmin()) {
            return true;
        }


        if ($this->params->get('counter', 0)) {
            $isActive = JFactory::getApplication()->input->getWord('view') != 'article' && ($context == 'com_content.featured' || $context == 'com_content.category');
            if ($isActive) {
                return $this->addWidget($row, false);
            }
        }

        return true;
    }

    private function addWidget(&$row, $view)
    {
        if ($this->isActiveInCategory($row->catid) == false) {
            return;
        }

        $uri = JUri::getInstance();
        $shortName = trim($this->params->get("shortname"));

        // http://blog.disqus.com/post/397517128/making-disqus-faster

        if ($view == 'article') {
            $output = "    <div id=\"disqus_thread\"></div>
                <script type=\"text/javascript\">
                    var disqus_shortname = '".$shortName."';
                    (function() {
                        var dsq = document.createElement('script'); dsq.type = 'text/javascript'; dsq.async = true;
                        dsq.src = '//' + disqus_shortname + '.disqus.com/embed.js';
                        (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(dsq);
                    })();
                </script>
                <noscript>Please enable JavaScript to view the <a href=\"http://disqus.com/?ref_noscript\">comments powered by Disqus.</a></noscript>
                <a href=\"http://disqus.com\" class=\"dsq-brlink\">comments powered by <span class=\"logo-disqus\">Disqus</span></a>";

            $row->text .= $output;
        } else {
            require_once(JPATH_ROOT . '/components/com_content/helpers/route.php');
            $link = $uri->toString(array('scheme', 'host')) . JRoute::_(ContentHelperRoute::getArticleRoute($row->id, $row->catid));

            $icon = "";
            if ($this->params->get('showIcon', 1)) {
                $document = JFactory::getDocument();

                //use a javascript to ensure css inside is load only once and defer/async
                $document->addScriptVersion(Juri::base() .'media/plg_content_ceddisquscomments/js.js');
                $document->addStyleSheet('//netdna.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css');

                $icon = $this->params->get('icon', 'fa-comment');
                $iconSize = $this->params->get('icon-size', '');
                $icon = '<span class="fa '. $icon .' '.$iconSize.' pull-left fa-border"></span>';
            }

            $output = "<script type=\"text/javascript\">
                    var disqus_shortname = '".$shortName."';
                    (function () {
                        var s = document.createElement('script'); s.async = true;
                        s.type = 'text/javascript';
                        s.src = '//' + disqus_shortname + '.disqus.com/count.js';
                        (document.getElementsByTagName('HEAD')[0] || document.getElementsByTagName('BODY')[0]).appendChild(s);
                    }());
                    </script>
                    ";

            $output .= '<a href="'.$link.'#disqus_thread">'.$icon.JText::_('PLG_CONTENT_CEDDISQUSCOMMENTS_ADD_COMMENTS').' </a>';

            return $output;
        }

    }

	public function isActiveInCategory($categoryId)
	{
		$categoryMode       = intval($this->params->get('categoryMode', 0));
		$selectedCategories = $this->params->get('includedCatIds');

		if ($categoryMode == 0)
		{
			return true;
		}

		if ($categoryMode == 1)
		{
			if ($selectedCategories == null)
			{
				return false;
			}

			return $this->isSelectedInCategory($selectedCategories, $categoryId);
		}

		return !$this->isSelectedInCategory($selectedCategories, $categoryId);
	}

	private function isSelectedInCategory($selectedCategories, $categoryId) {
		$match = false;
		if (is_array($selectedCategories))
		{
			foreach ($selectedCategories as $category)
			{
				if ($category === "")
				{ // all category is in the list
					return true;
				}
				if (strcmp(trim($category), $categoryId) == 0)
				{
					$match = true;
				}
			}
		}
		return $match;
	}

}
