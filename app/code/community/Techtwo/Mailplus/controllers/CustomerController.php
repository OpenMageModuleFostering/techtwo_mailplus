<?php
/*
 * Copyright 2014 MailPlus
*
* Licensed under the Apache License, Version 2.0 (the "License"); you may not
* use this file except in compliance with the License. You may obtain a copy
* of the License at
*
* http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
* WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
* License for the specific language governing permissions and limitations
* under the License.
*/
require 'Mage/Newsletter/controllers/ManageController.php';

/**
 * Customers newsletter subscription controller
 *
 * This checks the mailplus user.
 * Magento subscriber is really weird, if you are registered on webshop 1, then subscribe on webshop 2 and refresh the account in webshop1, the customer_id is added int he store webshop 2 entry and the subscriber is set to on.
 * This could be a magento bug or it is not multi store.
 *
 * This at least fixes it to use the actual user permissions.
 */
class Techtwo_Mailplus_CustomerController extends Mage_Newsletter_ManageController
{

}
