<?php
	namespace parametermetric\water;
?>

<?php
?>

<?php
	class Brick {
		private array $flats;
		private array $touches;

		public function __construct() {
			$this->figureFlats();
			$this->touches = array();
		}

		public function figureFlats() {
			$this->flats = array(
				"home/analyze",
				"home/analyze/essay",
				"home/analyze/essay/ignore",
				"home/analyze/essay/spell",
				"home/analyze/essay/spell/power",
				"home/analyze/essay/restore",
				"home/analyze/checkupdate",
				"home/analyze/selfupdate",
				"home/analyze/fullcircle",
				"home/analyze/halfcircle",
				"home/square",
				"home/square/task",
				"home/square/task/left",
				"home/square/task/left/track",
				"home/square/task/flag",
				"home/square/tip",
				"home/machine",
				"home/machine/reach",
				"home/machine/dump",
				"home/machine/traffic",
				"home/machine/shuffle",
				"home/machine/calculator",
				"home/machine/specimen",
				"home/machine/dice",
				"home/machine/switch"
			);
		}
	}
?>

<?php
	//echo "<pre>water.php: \"Once boil done, at this point, it's executed and data in memory.\"</pre>";
?>