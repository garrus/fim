<?php

class Deal {

	const BUY_FEE_RATE = 0.006;
	const SELL_FEE_RATE = 0.005;

	public $type;
	public $amount;
	public $share;
	public $price;
	public $date;

	private function __construct(){}

	/**
	 * @return DealBuilder
	 */
	public static function build(){
		return new DealBuilder(new static);
	}

	/**
	 * @return float
	 */
	public function getFee(){

		if ($this->type == 'buy') {
			$fee = self::BUY_FEE_RATE * $this->amount / (1 + self::BUY_FEE_RATE);
		} else {
			$fee = $this->share * $this->price * self::SELL_FEE_RATE;
		}
		return $fee;
	}

	/**
	 * @return mixed
	 */
	public function getCashChange(){
		if ($this->type == 'buy') {
			return -$this->amount;
		} else {
			return $this->share * $this->price - $this->getFee();
		}
	}

	/**
	 * @param $cash
	 * @param $share
	 */
	public function run(&$cash, &$share){

		$cash += $this->getCashChange();
		if ($this->type == 'buy') {
			$share += ($this->amount - $this->getFee()) / $this->price;
		} else {
			$share -= $this->share;
		}
	}

	/**
	 * @return Deal[] array
	 */
	public static function loadAll(){
		$filename = __DIR__. '/data.bin';
		if (!file_exists($filename)) {
			return [];
		}
		$file = new SplFileObject($filename, 'r');
		$file->setFlags(SplFileObject::DROP_NEW_LINE | SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY);
		$deals = [];
		foreach ($file as $line) {
			$deal = unserialize($line);
			if ($deal instanceof static) {
				$deals[] = $deal;
			} else {
				trigger_error('Bad data detected. '. PHP_EOL. $line, E_USER_NOTICE);
			}
		}
		return $deals;
	}

	/**
	 * @return bool
	 */
	public function save(){
		$file = new SplFileObject(__DIR__. '/data.bin', 'a+');
		return null !== @$file->fwrite(serialize($this). PHP_EOL);
	}


	public static function predict($principal, $cash, $share){

		if ($share == 0) {
			return null;
		}

		$info = [];
		$levels = [10, 5, 3, 2, 1, 0, -1, -2, -3, -5, -10];
		$factor = ($share * (1 - self::SELL_FEE_RATE));
		foreach ($levels as $profitRate) {
			$info[$profitRate][0] = ($principal - $cash) * (1 + 0.01 * $profitRate) / $factor;
			$info[$profitRate][1] = ($principal * (1 + 0.01 * $profitRate) - $cash) / $factor;
		}

		return array_filter($info, function($val){
			return $val > 0;
		});
	}

}


/**
 * Class DealBuilder
 */
class DealBuilder{

	/**
	 * @var Deal
	 */
	protected $deal;

	/**
	 * @param Deal $deal
	 */
	public function __construct(Deal $deal){
		$this->deal = $deal;
	}

	/**
	 * @param $date
	 *
	 * @return $this|Deal|DealBuilder
	 */
	public function on($date){
		$this->deal->date = $date;
		return $this->prepare();
	}

	/**
	 * @param $price
	 *
	 * @return $this|Deal|DealBuilder
	 */
	public function at($price){
		$this->deal->price = $price;
		return $this->prepare();
	}

	/**
	 * @param $share
	 *
	 * @return $this|Deal|DealBuilder
	 */
	public function sell($share){
		if ($this->deal->type) {
			throw new BadMethodCallException;
		}
		$this->deal->type = 'sell';
		$this->deal->share = $share;
		return $this->prepare();
	}

	/**
	 * @param float $amount
	 *
	 * @return $this|Deal|DealBuilder
	 */
	public function buy($amount){
		if ($this->deal->type) {
			throw new BadMethodCallException;
		}
		$this->deal->type = 'buy';
		$this->deal->amount = $amount;
		return $this->prepare();
	}

	/**
	 * @return $this|Deal
	 */
	private function prepare(){
		$deal = $this->deal;
		if ($deal->price && $deal->date && $deal->type && ($deal->amount || $deal->share)) {
			return $deal;
		} else {
			return $this;
		}
	}

}