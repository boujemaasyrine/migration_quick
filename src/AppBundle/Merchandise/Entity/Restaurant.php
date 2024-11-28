<?php

namespace AppBundle\Merchandise\Entity;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\Financial\Entity\PaymentMethod;
use AppBundle\General\Entity\SyncCmdQueue;
use AppBundle\Security\Entity\User;
use AppBundle\Supervision\Entity\ProductSupervision;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use AppBundle\ToolBox\Traits\TimestampableTrait;

/**
 * Restaurant
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Merchandise\Repository\RestaurantRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Restaurant
{

    use TimestampableTrait;

    const FRANCHISE = 'FRANCHISE';
    const COMPANY = 'COMPANY';
    const COUNTRIES = [
        'bel', 'lux'
    ];

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string" ,length=20 ,nullable=true)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="manager", type="string", length=100,nullable=true)
     */
    private $manager;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=255,nullable=true)
     */
    private $address;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=20, nullable=true)
     */
    private $phone;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=20,nullable=true)
     */
    private $type;

    /**
     * @var boolean
     *
     * @ORM\Column(name="orderable", type="boolean")
     */
    private $orderable = true;

    /**
     * @var Transfer
     * @ORM\OneToMany(targetEntity="AppBundle\Merchandise\Entity\Transfer",mappedBy="restaurant")
     */
    private $transfers;

    /**
     * @var string
     * @ORM\Column(name="email",type="string",length=50,nullable=true)
     */
    private $email;

    /**
     * @var boolean
     * @ORM\Column(name="active",type="boolean")
     */
    private $active = false;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Merchandise\Entity\Supplier", cascade={"persist"}, inversedBy="restaurants")
     */
    private $suppliers;

    /**
     * @var string
     * @ORM\Column(name="lang",type="string",length=10,nullable=true, options={"default"="fr"})
     */
    private $lang;

    /**
     * @var string
     * @ORM\Column(name="customer_lang",type="string",length=10,nullable=true)
     */
    private $customerLang;

    /**
     * @var string
     * @ORM\Column(name="manager_email",type="string",length=100,nullable=true)
     */
    private $managerEmail;

    /**
     * @var string
     * @ORM\Column(name="manager_phone",type="string",length=100,nullable=true)
     */
    private $managerPhone;

    /**
     * @var string
     * @ORM\Column(name="dm_cf",type="string",length=100,nullable=true)
     */
    private $dmCf;

    /**
     * @var string
     * @ORM\Column(name="phone_dm_cf",type="string",length=100,nullable=true)
     */
    private $phoneDmCf;

    /**
     * @var string
     * @ORM\Column(name="zip_code",type="string",length=10,nullable=true)
     */
    private $zipCode;

    /**
     * @var string
     * @ORM\Column(name="city",type="string",length=50,nullable=true)
     */
    private $city;

    /**
     * @var string
     * @ORM\Column(name="btw_tva",type="string",length=50,nullable=true)
     */
    private $btwTva;

    /**
     * @var string
     * @ORM\Column(name="company_name",type="string",length=100,nullable=true)
     */
    private $companyName;

    /**
     * @var string
     * @ORM\Column(name="address_company",type="string",length=255,nullable=true)
     */
    private $addressCompany;

    /**
     * @var string
     * @ORM\Column(name="zip_code_company",type="string",length=10,nullable=true)
     */
    private $zipCodeCompany;

    /**
     * @var string
     * @ORM\Column(name="city_correspondance",type="string",length=50,nullable=true)
     */
    private $cityCorrespondance;

    /**
     * @var string
     * @ORM\Column(name="cy_ft_fp_lg",type="string",length=10,nullable=true)
     */
    private $cyFtFpLg;

    /**
     * @var string
     * @ORM\Column(name="type_charte",type="string",length=50,nullable=true)
     */
    private $typeCharte;

    /**
     * @var \DateTime
     * @ORM\Column(name="first_openning",type="date",nullable=true)
     */
    private $firstOpenning;

    /**
     * @var string
     * @ORM\Column(name="cluster",type="string",length=50,nullable=true)
     */
    private $cluster;

    /**
     * @var User
     * @ORM\ManyToMany(targetEntity="AppBundle\Security\Entity\User", mappedBy="eligibleRestaurants")
     */
    private $eligibleUsers;


    /**
     * @var string
     * @ORM\Column(name="ip_address",type="string",length=255,nullable=true)
     */
    private $ipAddress;

    /**
     * @var boolean
     * @ORM\Column(name="eft",type="boolean", nullable=TRUE,options={"default"=true})
     */
    private $eft = true;

    /**
     * @var PaymentMethod
     * @ORM\ManyToMany(targetEntity="AppBundle\Financial\Entity\PaymentMethod",inversedBy="restaurants")
     */
    private $paymentMethods;


    /**
     * @ORM\OneToMany(targetEntity="AppBundle\General\Entity\SyncCmdQueue", mappedBy="originRestaurant")
     */
    private $syncCmdQueues;


    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Administration\Entity\Parameter", mappedBy="originRestaurant",cascade={"persist"})
     */
    private $parameters;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Merchandise\Entity\Product", mappedBy="originRestaurant")
     */
    private $products;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Supervision\Entity\ProductSupervision", mappedBy="restaurants")
     * @var ArrayCollection $supervisionProducts
     */
    private $supervisionProducts;

    /**
     * @ORM\Column(name="country",type="string", nullable=TRUE)
     */
    private $country;

    //Gestion des recettes
    /**
     * @var boolean
     *
     * @ORM\Column(name="reusable", type="boolean", options={"default" = false}))
     */
    private $reusable = false;

    /**
     * @return bool
     */
    public function isReusable()
    {
        return $this->reusable;
    }

    /**
     * @param bool $reusable
     */
    public function setReusable($reusable)
    {
        $this->reusable = $reusable;
    }
    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Restaurant
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set orderable
     *
     * @param boolean $orderable
     *
     * @return Restaurant
     */
    public function setOrderable($orderable)
    {
        $this->orderable = $orderable;

        return $this;
    }

    /**
     * Get orderable
     *
     * @return boolean
     */
    public function getOrderable()
    {
        return $this->orderable;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->transfers = new ArrayCollection();
        $this->eligibleUsers = new ArrayCollection();
        $this->syncCmdQueues = new ArrayCollection();
        $this->parameters = new ArrayCollection();
        $this->products = new ArrayCollection();
        $this->suppliers = new ArrayCollection();
        $this->supervisionProducts = new ArrayCollection();
        $this->paymentMethods = new ArrayCollection();
    }

    /**
     * Add transfer
     *
     * @param \AppBundle\Merchandise\Entity\Transfer $transfer
     *
     * @return Restaurant
     */
    public function addTransfer(\AppBundle\Merchandise\Entity\Transfer $transfer)
    {
        $this->transfers[] = $transfer;

        return $this;
    }

    /**
     * Remove transfer
     *
     * @param \AppBundle\Merchandise\Entity\Transfer $transfer
     */
    public function removeTransfer(\AppBundle\Merchandise\Entity\Transfer $transfer)
    {
        $this->transfers->removeElement($transfer);
    }

    /**
     * Get transfers
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTransfers()
    {
        return $this->transfers;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return Restaurant
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param $code
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set manager
     *
     * @param string $manager
     *
     * @return Restaurant
     */
    public function setManager($manager)
    {
        $this->manager = $manager;

        return $this;
    }

    /**
     * Get manager
     *
     * @return string
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * Set address
     *
     * @param  string $address
     * @return Restaurant
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set phone
     *
     * @param  string $phone
     * @return Restaurant
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set type
     *
     * @param  string $type
     * @return Restaurant
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Add supplier
     *
     * @param \AppBundle\Merchandise\Entity\Supplier $supplier
     *
     * @return Restaurant
     */
    public function addSupplier(\AppBundle\Merchandise\Entity\Supplier $supplier)
    {
        $this->suppliers[] = $supplier;

        return $this;
    }

    /**
     * Remove supplier
     *
     * @param \AppBundle\Merchandise\Entity\Supplier $supplier
     */
    public function removeSupplier(\AppBundle\Merchandise\Entity\Supplier $supplier)
    {
        $this->suppliers->removeElement($supplier);
    }

    /**
     * Get suppliers
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSuppliers()
    {
        return $this->suppliers;
    }

    /**
     * Set lang
     *
     * @param string $lang
     *
     * @return Restaurant
     */
    public function setLang($lang)
    {
        $this->lang = $lang;

        return $this;
    }

    /**
     * Get lang
     *
     * @return string
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * Set customerLang
     *
     * @param string $customerLang
     *
     * @return Restaurant
     */
    public function setCustomerLang($customerLang)
    {
        $this->customerLang = $customerLang;

        return $this;
    }

    /**
     * Get customerLang
     *
     * @return string
     */
    public function getCustomerLang()
    {
        return $this->customerLang;
    }

    /**
     * Set managerEmail
     *
     * @param string $managerEmail
     *
     * @return Restaurant
     */
    public function setManagerEmail($managerEmail)
    {
        $this->managerEmail = $managerEmail;

        return $this;
    }

    /**
     * Get managerEmail
     *
     * @return string
     */
    public function getManagerEmail()
    {
        return $this->managerEmail;
    }

    /**
     * Set managerPhone
     *
     * @param string $managerPhone
     *
     * @return Restaurant
     */
    public function setManagerPhone($managerPhone)
    {
        $this->managerPhone = $managerPhone;

        return $this;
    }

    /**
     * Get managerPhone
     *
     * @return string
     */
    public function getManagerPhone()
    {
        return $this->managerPhone;
    }

    /**
     * Set dmCf
     *
     * @param string $dmCf
     *
     * @return Restaurant
     */
    public function setDmCf($dmCf)
    {
        $this->dmCf = $dmCf;

        return $this;
    }

    /**
     * Get dmCf
     *
     * @return string
     */
    public function getDmCf()
    {
        return $this->dmCf;
    }

    /**
     * Set phoneDmCf
     *
     * @param string $phoneDmCf
     *
     * @return Restaurant
     */
    public function setPhoneDmCf($phoneDmCf)
    {
        $this->phoneDmCf = $phoneDmCf;

        return $this;
    }

    /**
     * Get phoneDmCf
     *
     * @return string
     */
    public function getPhoneDmCf()
    {
        return $this->phoneDmCf;
    }

    /**
     * Set zipCode
     *
     * @param string $zipCode
     *
     * @return Restaurant
     */
    public function setZipCode($zipCode)
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    /**
     * Get zipCode
     *
     * @return string
     */
    public function getZipCode()
    {
        return $this->zipCode;
    }

    /**
     * Set city
     *
     * @param string $city
     *
     * @return Restaurant
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set btwTva
     *
     * @param string $btwTva
     *
     * @return Restaurant
     */
    public function setBtwTva($btwTva)
    {
        $this->btwTva = $btwTva;

        return $this;
    }

    /**
     * Get btwTva
     *
     * @return string
     */
    public function getBtwTva()
    {
        return $this->btwTva;
    }

    /**
     * Set companyName
     *
     * @param  string $companyName
     * @return Restaurant
     */
    public function setCompanyName($companyName)
    {
        $this->companyName = $companyName;

        return $this;
    }

    /**
     * Get companyName
     *
     * @return string
     */
    public function getCompanyName()
    {
        return $this->companyName;
    }

    /**
     * Set addressCompany
     *
     * @param  string $addressCompany
     * @return Restaurant
     */
    public function setAddressCompany($addressCompany)
    {
        $this->addressCompany = $addressCompany;

        return $this;
    }

    /**
     * Get addressCompany
     *
     * @return string
     */
    public function getAddressCompany()
    {
        return $this->addressCompany;
    }

    /**
     * Set zipCodeCompany
     *
     * @param  string $zipCodeCompany
     * @return Restaurant
     */
    public function setZipCodeCompany($zipCodeCompany)
    {
        $this->zipCodeCompany = $zipCodeCompany;

        return $this;
    }

    /**
     * Get zipCodeCompany
     *
     * @return string
     */
    public function getZipCodeCompany()
    {
        return $this->zipCodeCompany;
    }

    /**
     * Set cityCorrespondance
     *
     * @param  string $cityCorrespondance
     * @return Restaurant
     */
    public function setCityCorrespondance($cityCorrespondance)
    {
        $this->cityCorrespondance = $cityCorrespondance;

        return $this;
    }

    /**
     * Get cityCorrespondance
     *
     * @return string
     */
    public function getCityCorrespondance()
    {
        return $this->cityCorrespondance;
    }

    /**
     * Set cyFtFpLg
     *
     * @param  string $cyFtFpLg
     * @return Restaurant
     */
    public function setCyFtFpLg($cyFtFpLg)
    {
        $this->cyFtFpLg = $cyFtFpLg;

        return $this;
    }

    /**
     * Get cyFtFpLg
     *
     * @return string
     */
    public function getCyFtFpLg()
    {
        return $this->cyFtFpLg;
    }

    /**
     * Set typeCharte
     *
     * @param  string $typeCharte
     * @return Restaurant
     */
    public function setTypeCharte($typeCharte)
    {
        $this->typeCharte = $typeCharte;

        return $this;
    }

    /**
     * Get typeCharte
     *
     * @return string
     */
    public function getTypeCharte()
    {
        return $this->typeCharte;
    }

    /**
     * Set firstOpenning
     *
     * @param \DateTime $firstOpenning
     *
     * @return Restaurant
     */
    public function setFirstOpenning($firstOpenning)
    {
        $this->firstOpenning = $firstOpenning;

        return $this;
    }

    /**
     * Get firstOpenning
     *
     * @return \DateTime
     */
    public function getFirstOpenning()
    {
        return $this->firstOpenning;
    }

    /**
     * Set cluster
     *
     * @param  string $cluster
     * @return Restaurant
     */
    public function setCluster($cluster)
    {
        $this->cluster = $cluster;

        return $this;
    }

    /**
     * Get cluster
     *
     * @return string
     */
    public function getCluster()
    {
        return $this->cluster;
    }

    /**
     * Set active
     *
     * @param boolean $active
     *
     * @return Restaurant
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active
     *
     * @return boolean
     */
    public function getActive()
    {
        return $this->active;
    }

    public function isLux()
    {
        $code = strval($this->code);
        if ($code[0] == '6') {
            return true;
        }

        return false;
    }

    public function getShortCode()
    {
        $code = strval($this->getCode());

        return substr($code, 1);
    }

    /**
     * Add eligibleUsers
     *
     * @param  User $eligibleUsers
     * @return Restaurant
     */
    public function addEligibleUser(User $eligibleUsers)
    {
        $this->eligibleUsers[] = $eligibleUsers;

        return $this;
    }

    /**
     * Remove eligibleUsers
     *
     * @param User $eligibleUsers
     */
    public function removeEligibleUser(User $eligibleUsers)
    {
        $this->eligibleUsers->removeElement($eligibleUsers);
    }

    /**
     * Get eligibleUsers
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEligibleUsers()
    {
        return $this->eligibleUsers;
    }

    /**
     * @return string
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * @param string $ipAddress
     */
    public function setIpAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;
    }

    /**
     * @return bool
     */
    public function getEft()
    {
        return $this->eft;
    }

    /**
     * @param bool $eft
     */
    public function setEft($eft)
    {
        $this->eft = $eft;
    }


    /**
     * Add paymentMethods
     *
     * @param  \AppBundle\Financial\Entity\PaymentMethod $paymentMethods
     * @return Restaurant
     */
    public function addPaymentMethod(PaymentMethod $paymentMethods)
    {
        $this->paymentMethods[] = $paymentMethods;

        return $this;
    }

    /**
     * Remove paymentMethods
     *
     * @param \AppBundle\Financial\Entity\PaymentMethod $paymentMethods
     */
    public function removePaymentMethod(PaymentMethod $paymentMethods)
    {
        $this->paymentMethods->removeElement($paymentMethods);
    }

    /**
     * Get paymentMethods
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPaymentMethods()
    {
        return $this->paymentMethods;
    }

    /**
     * @param $type
     * @return bool
     */
    public function hasPaymentMethodByType($type)
    {
        $types = array();
        $methods = $this->getPaymentMethods();
        if(empty($methods))
            return false;

        foreach ($methods as $method) {
            /**
             * @var PaymentMethod $method
             */
            $types[] = $method->getType();
        }
        if (in_array($type, $types)) {
            return true;
        }

        return false;
    }

    public function getPaymentMethodByType($type)
    {
        $result = array();
        foreach ($this->getPaymentMethods() as $paymentMethod) {
            if ($paymentMethod->getType() == $type) {
                $result[] = $paymentMethod;
            }
        }

        return $result;
    }


    public function getSyncCmdQueues()
    {
        return $this->syncCmdQueues;
    }

    public function addSyncCmdQueues(SyncCmdQueue $syncCmdQueue)
    {
        $this->syncCmdQueues->add($syncCmdQueue);
    }

    public function deleteSyncCmdQueues(SyncCmdQueue $syncCmdQueue)
    {
        $this->syncCmdQueues->removeElement($syncCmdQueue);
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    }

    public function addParameter(Parameter $parameter)
    {
        $parameter->setOriginRestaurant($this);
        $this->parameters->add($parameter);
    }

    public function deleteParameter(Parameter $parameter)
    {
        $this->parameters->removeElement($parameter);
    }

    public function getProducts()
    {
        return $this->products;
    }

    public function addProduct(Product $product)
    {
        $this->products->add($product);
    }

    public function deleteProduct(Product $product)
    {
        $this->products->removeElement($product);
    }

    /**
     * @return ArrayCollection
     */
    public function getSupervisionProducts()
    {
        return $this->supervisionProducts;
    }

    public function addSupervisionProduct(ProductSupervision $product)
    {
        $this->supervisionProducts->add($product);
    }

    public function deleteSupervisionProduct(ProductSupervision $product)
    {
        $this->supervisionProducts->removeElement($product);
    }

    public function getSuppliersIds()
    {
        $ids = array();
        $sup = $this->getSuppliers();
        foreach ($sup as $supplier) {
            $ids[] = $supplier->getId();
        }

        return $ids;
    }

    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param mixed $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

}
