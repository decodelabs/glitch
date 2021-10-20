<?php
/**
 * This is a stub file for IDE compatibility only.
 * It should not be included in your projects.
 */
namespace DecodeLabs;
use DecodeLabs\Veneer\Proxy;
use DecodeLabs\Veneer\ProxyTrait;
use DecodeLabs\Glitch\Context as Inst;
class Glitch implements Proxy { use ProxyTrait; 
const VENEER = 'Glitch';
const VENEER_TARGET = Inst::class;
const VERSION = Inst::VERSION;
};
