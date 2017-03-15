<?php

	/*
		Copyright (c) 2010, J. Palencia (ciachn@gmail.com)
		All rights reserved.

		THIS SOFTWARE IS PROVIDED BY <COPYRIGHT HOLDER> ''AS IS'' AND ANY
		EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
		WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
		DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
		DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
		(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
		LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
		ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
		(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
		SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
	*/

	/**
	**	@prot:	class Config
	**
	**	@desc:	Provides an interface to retrieve global configuration options.
	*/

	class Config
	{
		/**
		**	@prot:	public static $Config;
		**
		**	@desc:	The global Config instance, you just have to build one instance and 
		**			it will be recorded inside the object itself to maintain the reference.
		*/

		public static $Config = null;

		/**
		**	@prot:	public $CONFIG;
		**
		**	@desc:	Contains the configuration options, this is read from the array defined
		**			in the provided configuration file (see Constructor).
		*/

		public $CONFIG;

		/**
		**	@prot:	public function Config ($file)
		**
		**	@param:	$file [Default="@config.php"]
		**			The configuration file to load, must contain array named CONFIG, each
		**			item must be a path in the form CLASS/OPTION.
		**
		**	@desc:	Construct a Config object. A global array named "CONFIG" must be defined
		**			in the given file. That file will be included.
		*/

		public function Config ($file="@config.php")
		{
			/* Load the file. */
			include_once ($file);

			/* Store the config array. */
			$this->CONFIG = $CONFIG;

			/* Record the instance. */
			Config::$Config = $this;
		}

		/**
		**	@prot:	public static function get ($path)
		**
		**	@desc:	Returns an element from the configuration options, the given path
		**			locates the desired element.
		*/

		public static function get ($path)
		{
			/* If the instance is not ready, build it now. */
			if (Config::$Config == null) new Config ();

			return Config::$Config->CONFIG[$path];
		}
	};

?>
