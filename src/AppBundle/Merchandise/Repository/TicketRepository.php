<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 28/03/2016
 * Time: 08:01
 */

namespace AppBundle\Merchandise\Repository;

use Doctrine\ORM\EntityRepository;

// TODO: to be removed => getConsumedQtyBetweenDates ?
class TicketRepository extends EntityRepository
{

    public function getConsumedQtyBetweenDates(\DateTime $start, \DateTime $end)
    {

        $sql =
            <<<EOF
-- Qantités vendues des produits Non transformés sur une période
SELECT
  ps.product_purchased_id as "product" , sum(tl.qty) as "qty"
FROM
  ticket_line tl JOIN ticket t ON t.id = tl.ticket_id, product_sold ps
WHERE
  ps.code_plu = tl.plu AND
  ps.product_purchased_id is not NULL  AND
  t.date >= '2016-01-01' AND
  t.date <= '2016-12-31'
GROUP BY ps.product_purchased_id

UNION

-- Qantités vendues des produits transformés sur une période
SELECT
   rl.product_purchased_id as "product", sum(tl.qty) as "qty"
FROM
    ticket_line tl JOIN ticket t ON t.id = tl.ticket_id, product_sold ps
    JOIN recipe r ON ps.id = r.product_sold_id
    JOIN recipe_line rl ON r.id = rl.recipe_id
WHERE
  tl.plu = ps.code_plu AND
  t.date >= '2016-01-01' AND
  t.date <= '2016-12-31'
GROUP BY rl.product_purchased_id
EOF;

        //Todo to complete
    }
}
