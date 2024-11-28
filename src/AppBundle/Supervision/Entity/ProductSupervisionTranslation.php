<?php
/**
 * Created by PhpStorm.
 * User: bbarhoumi
 * Date: 16/05/2016
 * Time: 11:36
 */

namespace AppBundle\Supervision\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Entity\MappedSuperclass\AbstractPersonalTranslation;

/**
 * @ORM\Entity
 * @ORM\Table(name="product_supervision_translations",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="lookup_unique_id_product_supervision", columns={
 *         "locale", "object_id", "field"
 *     })}
 * )
 */
class ProductSupervisionTranslation extends AbstractPersonalTranslation
{

    /**
     * Convenient constructor
     *
     * @param string $locale
     * @param string $field
     * @param string $value
     */
    public function __construct($locale, $field, $value)
    {
        $this->setLocale($locale);
        $this->setField($field);
        $this->setContent($value);
    }


    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Supervision\Entity\ProductSupervision", inversedBy="translations")
     * @ORM\JoinColumn(name="object_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $object;
}
