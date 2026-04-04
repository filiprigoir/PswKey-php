<?php
declare(strict_types=1);

namespace PswKey\Validator;

/** 
 * Inject ErrorHandling-methods in a class
*/
trait InputHandler {
    protected ?string $_systemMessage = null;
    protected ?string $_customerMessage = null;
    protected ?string $_warningMessage = null;
    protected string $_info = "class"; //moet verwijderd worden in definitieve versie
    protected bool $_status = true;
    
    /**
     * Set error message via fluent interface @return Self
     */
    protected function setErrorMessage(string $systemMess) : self {
        $this->_systemMessage = $systemMess;
        return $this;
    }

    /**
     * Set warning message via fluent interface @return Self
     */
    protected function setWarningMessage(string $warningMess) : self {
        $this->_warningMessage = $warningMess;
        return $this;
    }

    /**
     * Set a customer message via fluent interface @return Self
     */
    protected function setCustomerMessage(string $customerMess) : self {
        $this->_customerMessage = $customerMess;
        return $this;
    }

    /** 
     * Set status true or false with fluent interface @return Self 
     */
    protected function setErrorStatus(bool $status) : self {
        $this->_status = $status;
        return $this;
    }

    /** 
     * moet weg bij definiteve versie
     */
    protected function setInfo(string $info) : self {
        $this->_info = $info;
        return $this;
    }

    /** 
     * Resset the message @return Void 
     */
    public function resetValidator() : void {
        if(!$this->_status) {
            $this->_customerMessage = null;
            $this->_systemMessage = null;
            $this->_status = true;
            $this->_warningMessage = null;            
        }
    }

    /**  
     * Serve the status of a validation @return \stdClass
     */
    public function status() : \stdClass {
        $validate = new \stdClass();
        $validate->name = $this->classMethodName();
        $validate->system = $this->_systemMessage;
        $validate->customer = $this->_customerMessage;
        $validate->warning = $this->_warningMessage;
        $validate->valid = $this->_status;
        $validate->invalid = !$this->_status;
        return $validate;
    }

    /**
     * contract wich class handles the error @return String
     */
    protected function classMethodName() : string {
        return \basename(\str_replace("\\", "/", \get_class($this))). "::" . $this->_info;
    }
}