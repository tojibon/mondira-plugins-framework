<?php         
if(!class_exists('DocsGenerator')){ 
    class DocsGenerator {
	var $title;
	var $docs;
	var $config;
    var $plugins_slug;
	function DocsGenerator($config, $plugins_slug, $title,$docs) {
        $this->config = $config;
        $this->plugins_slug = $plugins_slug;
		$this->title = $title;
		$this->docs = $docs;
		$this->render();
	}
	
	function render() {
		echo '<div class="wrap mondira-docs-page">';
		echo '<div id="icon-'.$this->plugins_slug.'" class="icon32 icon32-posts-'.$this->plugins_slug.'"><br></div><h2>'.$this->title.'</h2>';
		
		echo '<div id="mondira-docs-tabs"><ul class="mondira-docs-tabs">';
		foreach($this->docs as $docs) {
			echo '<li><a href="#'.$docs['section'].'">'.$docs['name'].'</a><span></span></li>';
		}
		echo '</ul>';
		foreach($this->docs as $docs) {
			$this->renderSection($docs['section']);
		}
		echo '<div class="clear"></div>';
		echo '</div>';
		echo '</div>';
	}
	
	function renderSection($section) {
		echo '<div id="'.$section.'" class="block">';
        if(file_exists($this->config[$this->plugins_slug]['MONDIRA_PLUGINS_FRAMEWORK_ADMIN_DOCS_DIR'].'/'.$section.'.php'))
		    include $this->config[$this->plugins_slug]['MONDIRA_PLUGINS_FRAMEWORK_ADMIN_DOCS_DIR'].'/'.$section.'.php';
        else if(file_exists($this->config[$this->plugins_slug]['MONDIRA_PLUGINS_DOCS_DIR'].'/'.$section.'.php'))
            include $this->config[$this->plugins_slug]['MONDIRA_PLUGINS_DOCS_DIR'].'/'.$section.'.php';
		echo '<div class="clear"></div>';
		echo '</div>';
	}
}
}