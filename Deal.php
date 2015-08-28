<?php

class Deal {

	const BUY_FEE_RATE = 0.006;
	const SELL_FEE_RATE = 0.005;

	public $type;
	public $amount;
	public $stock;
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
			return $this->amount * self::BUY_FEE_RATE;
		} else {
			return $this->stock * $this->price * self::SELL_FEE_RATE;
		}
	}

	/**
	 * @return mixed
	 */
	public function getCashChange(){
		if ($this->type == 'buy') {
			return -$this->amount;
		} else {
			return $this->stock * $this->price - $this->getFee();
		}
	}

	/**
	 * @param $cash
	 * @param $stock
	 */
	public function run(&$cash, &$stock){

		$cash += $this->getCashChange();
		if ($this->type == 'buy') {
			$stock += ($this->amount - $this->getFee()) / $this->price;
		} else {
			$stock -= $this->stock;
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


	public static function predict($total, $cash, $stock){

		if ($stock == 0) {
			return null;
		}

		$info = [];
		$levels = [20, 10, 5, 3, 2, 1, 0, -1, -2, -3, -5, -10];
		$factor = ($stock * (1 - self::SELL_FEE_RATE));
		foreach ($levels as $profitRate) {
			$info[$profitRate] = ($total * (1 + 0.01 * $profitRate) - $cash) / $factor;
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
	 * @param $stock
	 *
	 * @return $this|Deal|DealBuilder
	 */
	public function sell($stock){
		if ($this->deal->type) {
			throw new BadMethodCallException;
		}
		$this->deal->type = 'sell';
		$this->deal->stock = $stock;
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
		if ($deal->price && $deal->date && $deal->type && ($deal->amount || $deal->stock)) {
			return $deal;
		} else {
			return $this;
		}
	}

}