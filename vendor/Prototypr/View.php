<?php

namespace Prototypr;

class View {

	use ExtendTrait;

	protected $kernel;
	protected $data = [];

	protected $queue = [
		'canonical' => [],
		'manifest' => [],
		'favicon' => [],
		'css' => [],
		'js' => [],
	];

	public function __construct(array $opts=[], $merge=true) {
		//set opts
		foreach($opts as $k => $v) {
			//property exists?
			if(property_exists($this, $k)) {
				//is array?
				if($merge && $this->$k === (array) $this->$k) {
					$this->$k = array_merge($this->$k, $v);
				} else {
					$this->$k = $v;
				}
			}
		}
	}

	public function queue($type, $content, array $dependencies=[]) {
		//valid type?
		if(!isset($this->queue[$type])) {
			throw new \Exception("Asset queue only supports the following types: " . implode(', ', array_keys($this->queue)));
		}
		//set vars
		$html = '';
		$url = $this->url($content);
		$id = $url ? str_replace('.min', '', pathinfo($url, PATHINFO_FILENAME)) : md5($content);
		//is canonical?
		if($type === 'canonical') {
			if($url) {
				$html = '<link rel="canonical" href="' . $url . '">';
			}
		}
		//is manifest?
		if($type === 'manifest') {
			if($url) {
				$html = '<link rel="manifest" href="' . $url . '">';
			}
		}
		//is favicon?
		if($type === 'favicon') {
			if($url) {
				$html = '<link rel="icon" href="' . $url . '">';
			}
		}
		//is css?
		if($type === 'css') {
			if($url) {
				$html = '<link rel="stylesheet" href="' . $url . '">';
			} else {
				$html = '<style>' . strip_tags($content) . '</style>';
			}
		}
		//is js?
		if($type === 'js') {
			if($url) {
				$html = '<script defer src="' . $url . '"></script>';
			} else {
				$html = '<script type="module">' . strip_tags($content) . '</script>';
			}
		}
		//add to array
		$this->queue[$type][$id] = [
			'html' => $html,
			'deps' => $dependencies,
		];
		//return
		return $id;
	}

	public function dequeue($type, $id) {
		//unqueue asset?
		if(isset($this->queue[$type]) && isset($this->queue[$type][$id])) {
			unset($this->queue[$type][$id]);
		}
	}

	public function tpl($name, array $data=[], $isPrimary=false) {
		//set vars
		$themePath = '';
		$tplPath = $name;
		//is primary?
		if($isPrimary) {
			//set defaults
			$data = array_merge([
				'js' => [],
				'meta' => [],
			], $data);
			//loop through default config vars
			foreach([ 'base_url', 'env', 'name', 'route.path' ] as $param) {
				//get value
				$val = $this->kernel->config($param);
				//set value?
				if($val !== null) {
					$key = str_replace('base_url', 'baseUrl', explode('.', $param)[0]);
					$data['js'][$key] = $val;
				}
			}
			//has noindex?
			if(!isset($data['meta']['noindex']) && $this->kernel->config('env') !== 'prod') {
				$data['meta']['noindex'] = true;
			}
			//use theme?
			if($theme = $this->kernel->config('theme')) {
				//update paths
				$themePath = $this->kernel->config('modules_dir') . '/' . $theme;
				$tplPath = $themePath . '/layout.tpl';
				$fnPath = $themePath . '/functions';
				//load functions?
				if(is_dir($fnPath)) {
					//create recursive iterator
					$dir = new \RecursiveDirectoryIterator($fnPath);
					$iterator = new \RecursiveIteratorIterator($dir);
					$matches = new \RegexIterator($iterator, '/\.php$/', \RecursiveRegexIterator::MATCH);
					//loop through matches
					foreach($matches as $file) {
						require_once($file);
					}
				}
			}
		}
		//add default ext?
		if(strpos($tplPath, '.') === false) {
			$tplPath .= '.tpl';
		}
		//path found?
		if(!$tplPath = $this->kernel->path($tplPath)) {
			throw new \Exception($themePath ? "Theme layout.tpl not found" : "Template $name not found");
		}
		//merge data
		$this->data = array_merge($this->data, $data);
		//set template path?
		if(!isset($this->data['template'])) {
			$this->data['template'] = $name;
		}
		//view closure
		$fn = function($__tpl, $tpl) {
			include($__tpl);
		};
		//buffer
		ob_start();
		//load view
		$fn($tplPath, $this);
		//get html
		$html = ob_get_clean();
		//is primary?
		if($isPrimary) {
			//head html
			$head = '';
			//add queued asset types
			foreach($this->queue as $type => $items) {
				//loop through items
				foreach($items as $id => $item) {
					//TO-DO: Resolve dependencies
					$head .= $item['html'] . "\n";
				}
			}
			//add js vars?
			if($data['js']) {
				$head .= '<script>window.pageData = ' . json_encode($this->clean($data['js'])) . ';</script>' . "\n";
			}
			//add to head?
			if(!empty($head)) {
				$html = str_replace('</head>', $head . '</head>', $html);
			}
			//filter output
			$html = $this->kernel->event('output.html', $html);
		}
		//display
		echo $html;
	}

	public function data($key, $clean='html') {
		//set vars
		$data = $this->data;
		$parts = explode('.', $key);
		//loop through parts
		foreach($parts as $i => $part) {
			//is config?
			if(!$i && $part === 'config') {
				$data = $this->kernel->config();
				continue;
			}
			//data exists?
			if(is_object($data)) {
				if(isset($data->$part)) {
					$data = $data->$part;
				} else {
					$data = null;
					break;
				}
			} else {
				if(isset($data[$part])) {
					$data = $data[$part];
				} else {
					$data = null;
					break;
				}
			}
		}
		//clean data?
		if($data) {
			$data = $this->clean($data, $clean);
		}
		//return
		return $data;
	}

	public function url($url='', $opts=[]) {
		//default opts
		$opts = array_merge([
			'time' => true,
		], $opts);
		//return
		return $this->kernel->url($url, $opts);
	}

	public function clean($value, $type='html') {
		return $this->kernel->clean($value, $type);
	
	}

}