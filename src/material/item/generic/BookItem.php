<?php

class BookItem extends Item{
	public function __construct($meta = 0, $count = 1){
		parent::__construct(BOOK, 0, $count, "Book");
	}

}