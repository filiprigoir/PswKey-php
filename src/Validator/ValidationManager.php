<?php
declare(strict_types=1);

namespace PswKey\Validator;

/** 
 * Inject Validation-methods in a class
*/

trait ValidationManager {
    protected ?string $_internalMessage = null;
    protected ?string $_clientMessage = null;
    protected ?string $_warningMessage = null;
    protected bool $_status = true;
    
    /**
     * Set error message via fluent interface @return Self
     */
    protected function setInternalMessage(string $internalMess) : self {
        $this->_internalMessage = $internalMess;
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
    protected function setClientMessage(string $clientMess) : self {
        $this->_clientMessage = $clientMess;
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
     * Resset the message @return Void 
     */
    public function resetValidator() : void {
        if(!$this->_status) {
            $this->_clientMessage = null;
            $this->_internalMessage = null;
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
        $validate->internalMessage = $this->_internalMessage;
        $validate->clientMessage = $this->_clientMessage;
        $validate->warningMessage = $this->_warningMessage;
        $validate->valid = $this->_status;
        $validate->invalid = !$this->_status;
        return $validate;
    }

    /**
     * contract wich class handles the error @return String
     */
    protected function classMethodName() : string {
        return \basename(\str_replace("\\", "/", \get_class($this)));
    }
}