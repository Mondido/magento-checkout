<?php
class Mondido_Payment_Model_Api2_Group_Rest_Guest_V1 extends Mage_Api2_Model_Resource
{

    /**
     * Create a customer group
     * @return array
     */

    public function _create() {
        //Create Customer Group
        $requestData = $this->getRequest()->getBodyParams();
        $groupName = $requestData['name'];
        Mage::getSingleton('customer/group')->setData(
            array('customer_group_code' => $groupName,'tax_class_id' => 3))
            ->save();

        $targetGroup = Mage::getSingleton('customer/group');
        $groupId = $targetGroup->load($groupName, 'customer_group_code')->getId();

        if($groupId) {
            $json = array('id' => $groupId);
            echo json_encode($json);
            exit();
        }

    }

    public function _update() {
        die('qweqweqw');

        //Create Customer Group
        $requestData = $this->getRequest()->getBodyParams();
        $groupName = $requestData['name'];
        Mage::getSingleton('customer/group')->setData(
            array('customer_group_code' => $groupName,'tax_class_id' => 3))
            ->save();

        $targetGroup = Mage::getSingleton('customer/group');
        $groupId = $targetGroup->load($groupName, 'customer_group_code')->getId();

        if($groupId) {
            $json = array('id' => $groupId);
            echo json_encode($json);
            exit();
        }

    }

    /**
     * Retrieve a group name by ID
     * @return string
     */

    public function _retrieve()
    {
        die('5555');
        //retrieve a group name by ID
        $customerGroupId = $this->getRequest()->getParam('id');
        $groupname = Mage::getModel('customer/group')->load($customerGroupId)->getCustomerGroupCode();

        return $groupname;

    }

}
