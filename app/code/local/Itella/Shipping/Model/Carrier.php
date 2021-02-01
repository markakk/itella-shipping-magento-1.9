<?php

require Mage::getBaseDir() . '/lib/itella-api/vendor/autoload.php';

class Itella_Shipping_Model_Carrier extends Mage_Usa_Model_Shipping_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface {

    /**
     * Code of the carrier
     *
     * @var string
     */
    const CODE = 'itella';

    /**
     * Code of the carrier
     *
     * @var string
     */
    protected $_code = self::CODE;
    
     /**
     * Errors
     *
     * @var array
     */
    protected $globalErrors = [];

    /**
     * Rate request data
     *
     * @var Mage_Shipping_Model_Rate_Request|null
     */
    protected $_request = null;

    /**
     * Raw rate request data
     *
     * @var Varien_Object|null
     */
    protected $_rawRequest = null;

    /**
     * Rate result data
     *
     * @var Mage_Shipping_Model_Rate_Result|null
     */
    protected $_result = null;

    /**
     * Path to locations xml
     *
     * @var string
     */
    protected $_locationFileLt;
    protected $_locationFileEe;
    protected $_locationFileLv;
    protected $_locationFileFi;
    protected $isTest = 0;

    public function __construct() {
        parent::__construct();

        $this->isTest = (int) $this->getConfigData('is_test');

        $this->_locationFileLt = Mage::getModuleDir('etc', 'Itella_Shipping') . DS . 'location_lt.json';
        $this->_locationFileLv = Mage::getModuleDir('etc', 'Itella_Shipping') . DS . 'location_lv.json';
        $this->_locationFileEe = Mage::getModuleDir('etc', 'Itella_Shipping') . DS . 'location_ee.json';
        $this->_locationFileFi = Mage::getModuleDir('etc', 'Itella_Shipping') . DS . 'location_fi.json';

        if (!$this->getConfigData('location_update') || ($this->getConfigData('location_update') + 3600 * 24) < time() || !file_exists($this->_locationFileLt) || !file_exists($this->_locationFileLv) || !file_exists($this->_locationFileEe) || !file_exists($this->_locationFileFi)
        ) {
            $itellaPickupPointsObj = new \Mijora\Itella\Locations\PickupPoints('https://locationservice.posti.com/api/2/location');

            $itellaLoc = $itellaPickupPointsObj->getLocationsByCountry('LT');
            $itellaPickupPointsObj->saveLocationsToJSONFile($this->_locationFileLt, json_encode($itellaLoc));

            $itellaLoc = $itellaPickupPointsObj->getLocationsByCountry('LV');
            $itellaPickupPointsObj->saveLocationsToJSONFile($this->_locationFileLv, json_encode($itellaLoc));

            $itellaLoc = $itellaPickupPointsObj->getLocationsByCountry('EE');
            $itellaPickupPointsObj->saveLocationsToJSONFile($this->_locationFileEe, json_encode($itellaLoc));

            $itellaLoc = $itellaPickupPointsObj->getLocationsByCountry('FI');
            $itellaPickupPointsObj->saveLocationsToJSONFile($this->_locationFileFi, json_encode($itellaLoc));

            Mage::getModel('core/config')->saveConfig("carriers/itella/location_update", time());
        }
    }

    /**
     * Collect and get rates
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return Mage_Shipping_Model_Rate_Result|bool|null
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request) {

        if (!$this->getConfigData('active')) {
            return false;
        }
        $result = Mage::getModel('shipping/rate_result');
        //allow only with itella attribute
        /*
          $allow_itella = true;
          foreach ($request->getAllItems() as $item){
          $_item = $item->getProduct()->getId();
          $_product = Mage::getModel('catalog/product')->load($_item);
          $optionvalue = $_product->getItella();
          if (!$optionvalue){
          $allow_itella = false;
          break;
          }
          }
          if (!$allow_itella)
          return $result;
         */
        //end attribute check
        //Fetch the methods.
        $allowedMethods = $this->getAllowedMethods();
        $max_weight = $this->getConfigData('max_package_weight');
        foreach ($allowedMethods as $key => $title) {
            //Here check your method(carrier) if it is valid.
            //if is valid:
            //if ($request->getPackageWeight() > $max_weight) continue;

            $method = Mage::getModel('shipping/rate_result_method');
            $method->setCarrier($this->_code);
            $method->setCarrierTitle($this->getConfigData('title'));
            $method->setMethod($key);
            $method->setMethodTitle($title);
            $method->setMethodDescription($title);
            //Calculate shipping price for rate:
            //$shippingPrice = $this->_calculateShippingPrice($key); //You need to implement this method.
            $country_id = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getData('country_id');
            $subTotal = Mage::getModel('checkout/session')->getQuote()->getSubtotal();
            $free = ($this->getConfigData('free_shipping') && $subTotal >= $this->getConfigData('free_shipping_amount'));

            if ($key == "COURIER") {
                switch ($country_id) {
                    case 'LV':
                        $shippingPrice = $this->getConfigData('priceLV_C');
                        break;
                    case 'EE':
                        $shippingPrice = $this->getConfigData('priceEE_C');
                        break;
                    case 'FI':
                        $shippingPrice = $this->getConfigData('priceFI_C');
                        break;
                    default:
                        $shippingPrice = $this->getConfigData('priceLT_C');
                }
            }
            if ($key == "PARCEL_TERMINAL") {
                switch ($country_id) {
                    case 'LV':
                        $shippingPrice = $this->getConfigData('priceLV_pt');
                        break;
                    case 'EE':
                        $shippingPrice = $this->getConfigData('priceEE_pt');
                        break;
                    case 'FI':
                        $shippingPrice = $this->getConfigData('priceFI_pt');
                        break;
                    default:
                        $shippingPrice = $this->getConfigData('priceLT_pt');
                }
            }

            if ($free) {
                $shippingPrice = 0;
            }
            $method->setCost($shippingPrice);
            $method->setPrice($shippingPrice);
            //Finally add the method to the result.
            $result->append($method);
        }
        return $result;
    }

    /**
     * Get version of rates request
     *
     * @return array
     */
    public function getVersionInfo() {
        return array(
            'ServiceId' => 'crs',
            'Major' => '10',
            'Intermediate' => '0',
            'Minor' => '0'
        );
    }

    /**
     * Get configuration data of carrier
     *
     * @param string $type
     * @param string $code
     * @return array|bool
     */
    public function getCode($type, $code = '') {
        $codes = array(
            'method' => array(
                'COURIER' => __('Smartpost courier'),
                'PARCEL_TERMINAL' => __('Smartpost pickup point')
            ),
            'unit_of_measure' => array(
                'LB' => __('Pounds'),
                'KG' => __('Kilograms')
            ),
            'tracking' => array(
            ),
            'terminal' => array()
        );

        $codes['terminal']['LT'] = file_get_contents($this->_locationFileLt);
        $codes['terminal']['LV'] = file_get_contents($this->_locationFileLv);
        $codes['terminal']['EE'] = file_get_contents($this->_locationFileEe);
        $codes['terminal']['FI'] = file_get_contents($this->_locationFileFi);
        if ($type == "terminal" && $code == '')
            $code = "LT";

        if (!isset($codes[$type])) {
            return false;
        } elseif ('' === $code) {
            return $codes[$type];
        }

        if (!isset($codes[$type][$code])) {
            return false;
        } else {
            return $codes[$type][$code];
        }
    }

    /**
     * Get allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods() {
        $allowed = explode(',', $this->getConfigData('allowed_methods'));
        $arr = array();
        foreach ($allowed as $k) {
            $arr[$k] = $this->getCode('method', $k);
        }
        return $arr;
    }

    /**
     * For multi package shipments. Delete requested shipments if the current shipment
     * request is failed
     *
     * @param array $data
     * @return bool
     */
    public function rollBack($data) {
        /*
          $requestData = $this->_getAuthDetails();
          $requestData['DeletionControl'] = 'DELETE_ONE_PACKAGE';
          foreach ($data as &$item) {
          $requestData['TrackingId'] = $item['tracking_number'];
          $client = $this->_createShipSoapClient();
          $client->deleteShipment($requestData);
          }
         */
        return true;
    }

    public function call_Itella($date) {
        $sendTo = $this->getConfigData('courier_email');
        $orders = Mage::getModel('sales/order')->getCollection()
                ->addFieldToFilter('manifest_generation_date',array('like' => $date.'%')); 
        if (count($orders)){        
            foreach ($orders as $order) {
                $track_numer = '';
                $shipmentCollection = Mage::getResourceModel('sales/order_shipment_collection')->setOrderFilter($order)->load();
                foreach ($shipmentCollection as $shipment) {
                    foreach ($shipment->getAllTracks() as $tracknum) {
                        $track_numer .= $tracknum->getNumber() . ' ';
                    }
                }
                if ($track_numer == '') {
                    $text = 'Warning: Order ' . $order->getData('increment_id') . ' has no tracking number. Will not be included in manifest.';
                    Mage::getSingleton('adminhtml/session')->addWarning($text);
                    continue;
                }
                $count++;
                $shippingAddress = $order->getShippingAddress();
                $country = Mage::getModel('directory/country')->loadByCode($shippingAddress->country_id);
                $parcel_terminal_address = '';
                if ($order->getData('shipping_method') == 'itella_PARCEL_TERMINAL') {
                    $terminal_id = $order->getItellaPickupPoint();
                    $country = strtolower($order->getShippingAddress()->getCountryId());
                    $parcel_terminal = $this->_getItellaTerminal($terminal_id, $country);
                    if ($parcel_terminal) {
                        $parcel_terminal_address = $parcel_terminal['publicName'] . ', ' . $parcel_terminal['address']['streetName'] . ' ' . $parcel_terminal['address']['streetNumber'] . ', ' . $parcel_terminal['address']['postalCode'] . ', ' . $parcel_terminal['address']['postalCodeName'];
                        
                    }
                }
                $client_address = $shippingAddress->firstname . ' ' . $shippingAddress->lastname . ', ' . $shippingAddress->street . ', ' . $shippingAddress->postcode . ', ' . $shippingAddress->city . ' ' . $country;
                if ($parcel_terminal_address != '')
                    $client_address = '';
                $item = array(
                    'track_num' => $track_numer,
                    'weight' => $order->getWeight(),
                    'delivery_address' => $client_address . $parcel_terminal_address,
                );
                $items[] = $item;
                $order_table .= '<tr><td width = "40" align="right">' . $count . '.</td><td>' . $track_numer . '</td><td width = "60">' . date('Y-m-d') . '</td><td width = "40">1</td><td width = "60">' . $order->getWeight() . '</td><td width = "210">' . $client_address . $parcel_terminal_address . '</td></tr>';
            }
        } else {
            return false;
        }
        $translation = array(
            'sender_address' => __('Sender address'),
            'nr' => __('No.'),
            'track_num' => __('Tracking number'),
            'date' => __('Date'),
            'amount' => __('Amount'),
            'weight' => __('Weight').'(kg)',
            'delivery_address' => __('Delivery address'),
            'courier' => __('Courier'),
            'sender' => __('Sender'),
            'name_lastname_signature' => __('name, lastname, signature'),
        );
        
        $name = $this->getConfigData('cod_company');
        $phone = $this->getConfigData('company_phone');
        $street = $this->getConfigData('company_address');
        $postcode = $this->getConfigData('company_postcode');
        $city = $this->getConfigData('company_city');
        $country = $this->getConfigData('company_countrycode');
        
        $manifest = new \Mijora\Itella\Pdf\Manifest();


        $manifest_pdf = $manifest
                ->setStrings($translation)
                ->setSenderName($name)
                ->setSenderAddress($street)
                ->setSenderPostCode($postcode)
                ->setSenderCity($city)
                ->setSenderCountry($country)
                ->addItem($items)
                ->setToString(true)
                ->setBase64(true)
                ->printManifest('manifest.pdf');
        
        $items_fix = array();
        foreach ($items as $item) {
          $items_fix[] = array(
            'tracking_number' => $item['track_num'],
            'weight' => $item['weight'],
            'amount' => 1,
            'delivery_address' => $item['delivery_address']
          );
        }

        try {
            $caller = new \Mijora\Itella\CallCourier($sendTo);
            $result = $caller
                    ->setSenderEmail($this->getConfigData('company_email'))
                    ->setSubject('E-com order booking')
                    ->setPickUpAddress(array(
                        'sender' => $this->getConfigData('cod_company'),
                        'address_1' => $this->getConfigData('company_address'),
                        'postcode' => $this->getConfigData('company_postcode'),
                        'city' => $this->getConfigData('company_city'),
                        'country' => $this->getConfigData('company_countrycode'),
                        'pickup_time' => '8:00 - 17:00',
                        'contact_phone' => $this->getConfigData('company_phone'),
                    ))
                    ->setAttachment($manifest_pdf, true)
                    ->setItems($items_fix)
                    ->callCourier();
            if ($result) {
                return true;
            }
        } catch (\Throwable $th) {
            return false;
        }
        return false;
    }

    protected function _getItellaSender(Varien_Object $request) {
        try {
            $contract = '';
            if ($this->_getItellaShippingType($request) == \Mijora\Itella\Shipment\Shipment::PRODUCT_PICKUP) {
                $contract = $this->getConfigData('itella_contract_2711');
            }
            if ($this->_getItellaShippingType($request) == \Mijora\Itella\Shipment\Shipment::PRODUCT_COURIER) {
                $contract = $this->getConfigData('itella_contract_2317');
            }
            $sender = new \Mijora\Itella\Shipment\Party(\Mijora\Itella\Shipment\Party::ROLE_SENDER);
            $sender
                    ->setContract($contract)               // API contract number given by Itella
                    ->setName1($this->getConfigData('cod_company'))
                    ->setStreet1($this->getConfigData('company_address'))
                    ->setPostCode($this->getConfigData('company_postcode'))
                    ->setCity($this->getConfigData('company_city'))
                    ->setCountryCode($this->getConfigData('company_countrycode'))
                    ->setContactMobile($this->getConfigData('company_phone'))
                    ->setContactEmail($this->getConfigData('company_email'));
        } catch (Exception $e) {
            $this->globalErrors[] = $e->getMessage();
        }
        return $sender;
    }

    protected function _getItellaReceiver(Varien_Object $request) {
        try {
            $send_method = trim(str_ireplace('Itella_', '', $request->getShippingMethod()));

            $receiver = new \Mijora\Itella\Shipment\Party(\Mijora\Itella\Shipment\Party::ROLE_RECEIVER);
            $receiver
                    ->setName1($request->getRecipientContactPersonName())
                    ->setStreet1($request->getRecipientAddressStreet1())
                    ->setPostCode($request->getRecipientAddressPostalCode())
                    ->setCity($request->getRecipientAddressCity())
                    ->setCountryCode($request->getRecipientAddressCountryCode())
                    ->setContactName($request->getRecipientContactPersonName())
                    ->setContactMobile($request->getRecipientContactPhoneNumber())
                    ->setContactEmail($request->getOrderShipment()->getOrder()->getBillingAddress()->getEmail());
        } catch (Exception $e) {
            $this->globalErrors[] = $e->getMessage();
        }
        return $receiver;
    }

    public function _getItellaTerminal($id, $countryCode) {
        $locationFile = Mage::getModuleDir('etc', 'Itella_Shipping') . DS . '/location_' . strtolower($countryCode) . '.json';
        $terminals = array();
        if (file_exists($locationFile)) {
            $terminals = json_decode(file_get_contents($locationFile), true);
        } else {
            $itellaPickupPointsObj = new \Mijora\Itella\Locations\PickupPoints('https://locationservice.posti.com/api/2/location');
            $terminals = $itellaPickupPointsObj->getLocationsByCountry($countryCode);
        }
        if (count($terminals) > 0) {
            foreach ($terminals as $terminal) {
                if ($terminal['pupCode'] == $id) {
                    return $terminal;
                }
            }
        }
        $this->globalErrors[] = "Required terminal not found. Terminal ID: " . $id;
        return false;
    }
    
    
    protected function _getItellaServices(Varien_Object $request) {
        /*
          Must be set manualy
          3101 - Cash On Delivery (only by credit card). Requires array with this information:
          amount => amount to be payed in EUR,
          account => bank account (IBAN),
          codbic => bank BIC,
          reference => COD Reference, can be used Helper::generateCODReference($id) where $id can be Order ID.
          3104 - Fragile
          3166 - Call before Delivery
          3174 - Oversized
          Will be set automatically
          3102 - Multi Parcel, will be set automatically if Shipment has more than 1 and up to 10 GoodsItem. Requires array with this information:
          count => Total of registered GoodsItem.
         */
        $services = array();
        $send_method = trim(str_ireplace('Itella_', '', $request->getShippingMethod()));
        if ($send_method == "COURIER") {
            try {
                $itemsShipment = $request->getPackageItems();


                $order_services = $request->getOrderShipment()->getOrder()->getItellaServices();
                if ($order_services == null){
                    $order_services = array('services'=> array(), 'parcel_count' => '');
                } else {
                    $order_services = json_decode($order_services, true);
                }
                $multi_parcel_count = $order_services['parcel_count'];
                $order_services = $order_services['services'];

                if ($this->_isCod($request) || in_array(3101, $order_services)) {
                    $service_cod = new \Mijora\Itella\Shipment\AdditionalService(
                            Mijora\Itella\Shipment\AdditionalService::COD,
                            array(
                        'amount' => round($request->getOrderShipment()->getOrder()->getGrandTotal(), 2),
                        'codbic' => $this->getConfigData('company'),
                        'account' => $this->getConfigData('bank_account'),
                        'reference' => \Mijora\Itella\Helper::generateCODReference($request->getOrderShipment()->getOrder()->getId())
                            )
                    );
                    $services[] = $service_cod;
                }
                if (in_array(3104, $order_services)) {
                    $service_fragile = new \Mijora\Itella\Shipment\AdditionalService(\Mijora\Itella\Shipment\AdditionalService::FRAGILE);
                    $services[] = $service_fragile;
                }
                if (in_array(3166, $order_services)) {
                    $service = new \Mijora\Itella\Shipment\AdditionalService(\Mijora\Itella\Shipment\AdditionalService::CALL_BEFORE_DELIVERY);
                    $services[] = $service;
                }
                if (in_array(3174, $order_services)) {
                    $service = new \Mijora\Itella\Shipment\AdditionalService(\Mijora\Itella\Shipment\AdditionalService::OVERSIZED);
                    $services[] = $service;
                }
            } catch (Exception $e) {
                $this->globalErrors[] = $e->getMessage();
            }
        }
        return $services;
    }

    protected function _getItellaItems(Varien_Object $request) {
        $items = array();
        try {
            //$itemsShipment = $request->getPackageItems();
            $order_items = $request->getOrderShipment()->getOrder()->getAllItems();
            $total_weight = 0;
            foreach ($order_items as $order_item) {
                $total_weight += $order_item->getWeight() * $order_item->getQty();
            }
            $send_method = trim(str_ireplace('Itella_', '', $request->getShippingMethod()));
            if ($send_method == "COURIER") {
                $order_services = $request->getOrderShipment()->getOrder()->getItellaServices();
                if ($order_services == null){
                    $order_services = array('services'=> array(), 'parcel_count' => '');
                } else {
                    $order_services = json_decode($order_services, true);
                }
                $multi_parcel_count = $order_services['parcel_count'];
                $order_services = $order_services['services'];
                if (in_array(3102, $order_services) && $multi_parcel_count > 1 && $multi_parcel_count <=10) {
                    for ($i=1;$i<=$multi_parcel_count;$i++){
                        $item = new \Mijora\Itella\Shipment\GoodsItem();
                        $item->setGrossWeight(round($total_weight/$multi_parcel_count,3));
                        $items[] = $item;
                    }
                } else {
                    $item = new \Mijora\Itella\Shipment\GoodsItem();
                    $item->setGrossWeight($total_weight);
                    $items[] = $item;
                }
                
            } else {
                $item = new \Mijora\Itella\Shipment\GoodsItem();
                $item->setGrossWeight($total_weight);
                $items[] = $item;
            }
        } catch (Exception $e) {
            $this->globalErrors[] = $e->getMessage();
        }
        return $items;
    }

    protected function _isCod(Varien_Object $request) {
        $payment_method = $request->getOrderShipment()->getOrder()->getPayment()->getMethodInstance()->getCode();
        if (stripos($payment_method, 'cashondelivery') !== false || stripos($payment_method, 'cod') !== false) {
            return true;
        }
        return false;
    }
    
    protected function _getItellaShippingType(Varien_Object $request) {
        $send_method = trim(str_ireplace('Itella_', '', $request->getShippingMethod()));
        if ($send_method == "PARCEL_TERMINAL") {
            return Mijora\Itella\Shipment\Shipment::PRODUCT_PICKUP;
        }
        if ($send_method == "COURIER") {
            return Mijora\Itella\Shipment\Shipment::PRODUCT_COURIER;
        }
        return false;
    }

    protected function _doShipmentRequest(Varien_Object $request) {
        $tracking_number = false;
        try {
            $barcodes = array();
            $this->_prepareShipmentRequest($request);
            $result = new Varien_Object();
            $sender = $this->_getItellaSender($request);
            $receiver = $this->_getItellaReceiver($request);
            $items = $this->_getItellaItems($request);
            $services = $this->_getItellaServices($request);

            if (!empty($this->globalErrors)) {
                throw new Exception('Error: Order '.$request->getOrderShipment()->getOrder()->getIncrementId().' has errors.');
            }
            
            if ($this->_getItellaShippingType($request) == \Mijora\Itella\Shipment\Shipment::PRODUCT_PICKUP) {
                $shipment = new \Mijora\Itella\Shipment\Shipment($this->getConfigData('account_2711'), $this->getConfigData('password_2711'));
                $shipment
                        ->setProductCode(\Mijora\Itella\Shipment\Shipment::PRODUCT_PICKUP)
                        ->setPickupPoint($request->getOrderShipment()->getOrder()->getItellaPickupPoint());
            }
            if ($this->_getItellaShippingType($request) == \Mijora\Itella\Shipment\Shipment::PRODUCT_COURIER) {
                $shipment = new \Mijora\Itella\Shipment\Shipment($this->getConfigData('account_2317'), $this->getConfigData('password_2317'));
                $shipment->setProductCode(\Mijora\Itella\Shipment\Shipment::PRODUCT_COURIER);
            }
            $shipment
                    ->setShipmentNumber($request->getOrderShipment()->getOrder()->getIncrementId()) // Shipment/waybill identifier
                    ->setSenderParty($sender) // previously created Sender object
                    ->setReceiverParty($receiver) // previously created Receiver object
                    ->addAdditionalServices($services)
                    ->addGoodsItems($items); // array of previously created GoodsItem objects, can also be just GoodsItem onject

            $tracking_number = $shipment->registerShipment();
        } catch (Exception $e) {
            $this->globalErrors[] = $e->getMessage();
        }

        if (empty($this->globalErrors)) {
            //var_dump($items); exit;
            //echo $shipment->getXML()->asXML(); exit;
            $documentDateTime = $shipment->getDocumentDateTime();
            $sequence = $shipment->getSequence();
        } else {
            $result->setErrors(implode('<br/>', $this->globalErrors));
        }


        if ($result->hasErrors()) {
            return $result;
        } else {
            if ($tracking_number) {
                $label = $shipment->downloadLabels($tracking_number);
                $result->setShippingLabelContent(base64_decode($label));

                $result->setTrackingNumber(array($tracking_number));
                //var_dump($result); die;
                return $result;
            }
            $result->setErrors(__('No saved barcodes received'));
            return $result;
        }
    }

}
