<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 *
 * Glory to Ukraine! Glory to the heroes!
 */

namespace Magefan\LoginAsCustomer\Controller\Adminhtml\Login;

/**
 * LoginAsCustomer log action
 */
class Index extends \Magento\Backend\App\Action
{
    /**
     * @var \Magefan\LoginAsCustomer\Model\Login
     */
    protected $login = null;

    /**
     * Index constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magefan\LoginAsCustomer\Model\Login|null $login
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magefan\LoginAsCustomer\Model\Login $login = null
    ) {
        parent::__construct($context);
        $objectManager = $this->_objectManager;
        $this->login = $login ?: $objectManager->get(\Magefan\LoginAsCustomer\Model\Login::class);
    }
    /**
     * Login as customer log
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if ($this->getRequest()->getParam('ajax')) {
            $this->_forward('grid');
            return;
        }

        $this->login->deleteNotUsed();

        $this->_view->loadLayout();
        $this->_setActiveMenu('Magefan_LoginAsCustomer::login_log');
        $title = __('Login As Customer Log ');
        $this->_view->getPage()->getConfig()->getTitle()->prepend($title);
        $this->_addBreadcrumb($title, $title);
        $this->_view->renderLayout();
    }

    /**
     * Check is allowed access
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magefan_LoginAsCustomer::login_log');
    }
}
