<?php
/**
 * Created by PhpStorm.
 * User: bbarhoumi
 * Date: 16/05/2016
 * Time: 11:36
 */

namespace AppBundle\Administration\Entity\Translation;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Entity\MappedSuperclass\AbstractPersonalTranslation;

/**
 * @ORM\Entity
 * @ORM\Table(name="parameter_translations",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="lookup_unique_id_parameter", columns={
 *         "locale", "object_id", "field"
 *     })}
 * )
 */
class ParameterTranslation extends AbstractPersonalTranslation
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
     * @ORM\ManyToOne(targetEntity="AppBundle\Administration\Entity\Parameter", inversedBy="translations")
     * @ORM\JoinColumn(name="object_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $object;
}
