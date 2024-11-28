<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 29/03/2016
 * Time: 10:07
 */

namespace AppBundle\Administration\Entity\Optikitchen;

use AppBundle\Merchandise\Entity\Product;
use AppBundle\Merchandise\Entity\ProductPurchased;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * OptikitchenProduct
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class OptikitchenProduct
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=20,nullable=true)
     */
    private $type;

    /**
     * @var float
     *
     * @ORM\Column(name="coef", type="float",nullable=true)
     */
    private $coef;

    /**
     * @var float
     *
     * @ORM\Column(name="coef_by_day", type="float",nullable=true)
     */
    private $coefByDay;

    /**
     * @var Optikitchen
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Administration\Entity\Optikitchen\Optikitchen",inversedBy="products")
     */
    private $optikitchen;

    /**
     * @var
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\Product")
     */
    private $product;

    /**
     * @var
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Administration\Entity\Optikitchen\OptikitchenProductDetails",mappedBy="optiProduct",cascade={"persist","remove"})
     */
    private $details;


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
     * Set type
     *
     * @param string $type
     *
     * @return OptikitchenProduct
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
     * Set coef
     *
     * @param float $coef
     *
     * @return OptikitchenProduct
     */
    public function setCoef($coef)
    {
        $this->coef = $coef;

        return $this;
    }

    /**
     * Get coef
     *
     * @return float
     */
    public function getCoef()
    {
        return $this->coef;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->details = new ArrayCollection();
    }

    /**
     * Set optikitchen
     *
     * @param \AppBundle\Administration\Entity\Optikitchen\Optikitchen $optikitchen
     *
     * @return OptikitchenProduct
     */
    public function setOptikitchen(Optikitchen $optikitchen = null)
    {
        $this->optikitchen = $optikitchen;

        return $this;
    }

    /**
     * Get optikitchen
     *
     * @return Optikitchen
     */
    public function getOptikitchen()
    {
        return $this->optikitchen;
    }

    /**
     * Set product
     *
     * @param Product $product
     *
     * @return OptikitchenProduct
     */
    public function setProduct(Product $product = null)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get product
     *
     * @return \AppBundle\Merchandise\Entity\Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Add detail
     *
     * @param \AppBundle\Administration\Entity\Optikitchen\OptikitchenProductDetails $detail
     *
     * @return OptikitchenProduct
     */
    public function addDetail(OptikitchenProductDetails $detail)
    {
        $this->details[] = $detail;

        return $this;
    }

    /**
     * Remove detail
     *
     * @param \AppBundle\Administration\Entity\Optikitchen\OptikitchenProductDetails $detail
     */
    public function removeDetail(OptikitchenProductDetails $detail)
    {
        $this->details->removeElement($detail);
    }

    /**
     * Get details
     *
     * @return OptikitchenProductDetails[]
     */
    public function getDetails()
    {
        $details = $this->details->toArray();

        usort(
            $details,
            function (OptikitchenProductDetails $d1, OptikitchenProductDetails $d2) {

                if ($d1->getT1()->format('YmdHis') < $d2->getT1()->format('YmdHis')) {
                    return -1;
                }
                return 1;

            }
        );

        return $details;
    }

    /**
     * @param $h1
     * @param $m1
     * @param $h2
     * @param $m2
     *
     * @return float|int
     */
    public function getCoefInQuart($h1, $m1, $h2, $m2)
    {
        $t1 = str_pad($h1, 2, '0', STR_PAD_LEFT).':'.str_pad($m1, 2, '0', STR_PAD_LEFT);
        $t2 = str_pad($h2, 2, '0', STR_PAD_LEFT).':'.str_pad($m2, 2, '0', STR_PAD_LEFT);

        $details = null;
        foreach ($this->getDetails() as $d) {
            if ($d->getT1()->format('H:i') < $d->getT2()->format('H:i')) {
                if ($d->getT1()->format('H:i') <= $t1
                    && ($d->getT2()->format('H:i') >= $t2)
                ) {
                    $details = $d;
                    break;
                }
            } else {
                $dt2 = str_pad(strval(intval($d->getT2()->format('H')) + 24), 2, '0', STR_PAD_LEFT);
                $dt2 = $dt2.str_pad($d->getT2()->format('i'), 2, '0', STR_PAD_LEFT);
                if ($dt2 >= $t2
                ) {
                    $details = $d;
                    break;
                }
            }
        }


        if ($details) {
            return $details->getCoefPerFifteenMin();
        }

        return 0;
    }

    /**
     * @param OptikitchenMatrix[] $matrix
     *
     * @return array
     */
    public function getBinLevels($matrix)
    {

        $data = [];

        foreach ($matrix as $m) {
            $coef = 0;
            if($this->getCoef() && $this->getCoef()!=0){
                $coef = round($m->getAvg() / $this->getCoef() * $m->getValue());
            }
            else{
                if ($this->getCoefByDay() != 0) {
                    $coef = round($m->getAvg() / $this->getCoefByDay() * $m->getValue());
                }
            }


            $data[] = [
                'id' => $m->getLevel(),
                'qty' => $coef,
            ];
        }

        return $data;
    }

    /**
     * @param OptikitchenMatrix[] $matrix
     *
     * @return array
     */
    public function getForeCasts($matrix)
    {

        $data = [];
        foreach ($this->optikitchen->getDayParts() as $d) {
            //            $line = null;
            //            foreach($matrix as $m){
            //                if  ( ($m->getMin() <= $d['budget']) && ($m->getMax() > $d['budget'])  ){
            //                    $line = $m;
            //                    break;
            //                }
            //            }
            //
            //            if ($line){
            // var_dump("Coef in ".$d['startH'].$d['startM']." / ".$d['endH'].$d['endM']." ".$this->getCoefInQuart($d['startH'],$d['startM'],$d['endH'],$d['endM']));
            //                var_dump($line->getAvg());
            //                var_dump($line->getValue());
            //                var_dump($this->getCoefInQuart($d['startH'],$d['startM'],$d['endH'],$d['endM']));
            //                var_dump(round( $line->getAvg() / $this->getCoefInQuart($d['startH'],$d['startM'],$d['endH'],$d['endM']) * $line->getValue()));
            $qty = 0;
            if ($this->getCoefInQuart($d['startH'], $d['startM'], $d['endH'], $d['endM']) != 0) {
                $qty = round($d['budget'] / $this->getCoefInQuart($d['startH'], $d['startM'], $d['endH'], $d['endM']));
                //$qty = round( $line->getAvg() / $this->getCoefInQuart($d['startH'],$d['startM'],$d['endH'],$d['endM']) * $line->getValue()) ;
            }
            $data[] = [
                'id' => $d['id'],
                'qty' => $qty,
                'budget' => $d['budget'],
                'coefInQuart' => $this->getCoefInQuart($d['startH'], $d['startM'], $d['endH'], $d['endM']),
            ];
            //            }else{
            //                $data[] = [
            //                    'id' => $d['id'],
            //                    'qty' => 0
            //                ];
            //            }
        }

        return $data;
    }

    /**
     * Set coefByDay
     *
     * @param float $coefByDay
     *
     * @return OptikitchenProduct
     */
    public function setCoefByDay($coefByDay)
    {
        $this->coefByDay = $coefByDay;

        return $this;
    }

    /**
     * Get coefByDay
     *
     * @return float
     */
    public function getCoefByDay()
    {
        return $this->coefByDay;
    }
}
