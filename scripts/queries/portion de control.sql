SELECT * FROM (
SELECT 
  P.id as PRODUCT_ID,
  PG.id as category_id,
  PG.order,

/* Category name*/ PG.name as category_name,
/* Code */ 	   PP.external_id as code,
/* Name	*/	   P.name as description,
/* Format */	   PP.label_unit_inventory as format,
/* Initial */	   INITIAL.total_inventory_cnt as initial,
/* Entree */	   ENTREE.qty as entree,
/* Sortie */	   SORTIE.qty as sortie,
/* Final */	   FINAL.total_inventory_cnt as final,
/* Valeur Final */ PP.buying_cost * (FINAL.total_inventory_cnt/PP.inventory_qty) as valeur_final,
/* ventes */       VENTES.qty as ventes,
/* Item vts */     ITEM_VTES.qty as item_vtes,
/* Item inv */     ITEM_INV.qty as item_inv,
/* Theo */         VENTES.qty + ITEM_INV.qty + ITEM_VTES.qty as theo,
/* Reel */         INITIAL.total_inventory_cnt + ENTREE.qty - SORTIE.qty - FINAL.total_inventory_cnt as reel,
/* ecart */        VENTES.qty + ITEM_INV.qty + ITEM_VTES.qty - ( INITIAL.total_inventory_cnt + ENTREE.qty - SORTIE.qty - FINAL.total_inventory_cnt) as ecart,
/* valorisation */ VENTES.qty + ITEM_INV.qty + ITEM_VTES.qty - ( INITIAL.total_inventory_cnt + ENTREE.qty - SORTIE.qty - FINAL.total_inventory_cnt) * ( PP.buying_cost / (PP.inventory_qty * PP.usage_qty)) as valorisation,

  PP.usage_qty,
  PP.inventory_qty,

  PP.buying_cost,

  INITIAL.inventory_line_id as INITIAL_inventory_line_id,


  FINAL.inventory_line_id as final_inventory_line_id,
  PP.status as product_status

FROM public.product_purchased PP

/* Category name */
LEFT JOIN public.product P ON P.id = PP.id
LEFT JOIN public.product_categories PG ON PG.id = PP.product_category_id

/* Inventory lines */
/* INITIAL */
LEFT JOIN (
SELECT n2.* FROM (
SELECT IL.product_id, MIN(inventory_sheet.created_at) as created_at  FROM public.inventory_line IL
	LEFT JOIN public.inventory_sheet ON public.inventory_sheet.id = IL.inventory_sheet_id
	WHERE ( public.inventory_sheet.fiscal_date >= '2016-03-08 00:00:00' AND public.inventory_sheet.fiscal_date < '2016-03-25 00:00:00' )
	AND inventory_sheet.fiscal_date = (SELECT MIN(inventory_sheet.fiscal_date) FROM public.inventory_line IL1
	LEFT JOIN public.inventory_sheet ON public.inventory_sheet.id = IL1.inventory_sheet_id
	WHERE ( public.inventory_sheet.fiscal_date >= '2016-03-08 00:00:00' AND public.inventory_sheet.fiscal_date < '2016-03-25 00:00:00' ) AND IL1.product_id = IL.product_id)
	GROUP BY IL.product_id) n1

INNER JOIN
(SELECT IL.product_id, IL.total_inventory_cnt, IL.id as inventory_line_id, inventory_sheet.fiscal_date, inventory_sheet.created_at FROM public.inventory_line IL
	LEFT JOIN public.inventory_sheet ON public.inventory_sheet.id = IL.inventory_sheet_id
	WHERE ( public.inventory_sheet.fiscal_date >= '2016-03-08 00:00:00' AND public.inventory_sheet.fiscal_date < '2016-03-25 00:00:00' )
	AND inventory_sheet.fiscal_date = (SELECT MIN(inventory_sheet.fiscal_date) FROM public.inventory_line IL1
	LEFT JOIN public.inventory_sheet ON public.inventory_sheet.id = IL1.inventory_sheet_id
	WHERE ( public.inventory_sheet.fiscal_date >= '2016-03-08 00:00:00' AND public.inventory_sheet.fiscal_date < '2016-03-25 00:00:00' ) AND IL1.product_id = IL.product_id)
) n2
 ON  n1.product_id = n2.product_id AND n1.created_at = n2.created_at) INITIAL ON INITIAL.product_id = PP.id

/* FINAL */
LEFT JOIN (SELECT n2.* FROM (
SELECT IL.product_id, MAX(inventory_sheet.created_at) as created_at  FROM public.inventory_line IL
	LEFT JOIN public.inventory_sheet ON public.inventory_sheet.id = IL.inventory_sheet_id
	WHERE ( public.inventory_sheet.fiscal_date >= '2016-03-08 00:00:00' AND public.inventory_sheet.fiscal_date < '2016-03-25 00:00:00' )
	AND inventory_sheet.fiscal_date = (SELECT MIN(inventory_sheet.fiscal_date) FROM public.inventory_line IL1
	LEFT JOIN public.inventory_sheet ON public.inventory_sheet.id = IL1.inventory_sheet_id
	WHERE ( public.inventory_sheet.fiscal_date >= '2016-03-08 00:00:00' AND public.inventory_sheet.fiscal_date < '2016-03-25 00:00:00' ) AND IL1.product_id = IL.product_id)
	GROUP BY IL.product_id) n1

INNER JOIN
(SELECT IL.product_id, IL.total_inventory_cnt, IL.id as inventory_line_id, inventory_sheet.fiscal_date, inventory_sheet.created_at FROM public.inventory_line IL
	LEFT JOIN public.inventory_sheet ON public.inventory_sheet.id = IL.inventory_sheet_id
	WHERE ( public.inventory_sheet.fiscal_date >= '2016-03-08 00:00:00' AND public.inventory_sheet.fiscal_date < '2016-03-25 00:00:00' )
	AND inventory_sheet.fiscal_date = (SELECT MIN(inventory_sheet.fiscal_date) FROM public.inventory_line IL1
	LEFT JOIN public.inventory_sheet ON public.inventory_sheet.id = IL1.inventory_sheet_id
	WHERE ( public.inventory_sheet.fiscal_date >= '2016-03-08 00:00:00' AND public.inventory_sheet.fiscal_date < '2016-03-25 00:00:00' ) AND IL1.product_id = IL.product_id)
) n2
 ON  n1.product_id = n2.product_id AND   n1.created_at = n2.created_at
) FINAL ON FINAL.product_id = PP.id

/* Entrée */
LEFT JOIN(

SELECT  _ENTREE.product_id, SUM(_ENTREE.qty) as qty FROM
	/* Delivery lines */
(
 SELECT DL.product_id as product_id, SUM(DL.qty * product_purchased.inventory_qty ) as qty FROM public.delivery_line DL
	LEFT JOIN public.product_purchased product_purchased ON product_purchased.id = DL.product_id
	LEFT JOIN public.delivery D ON D.id = DL.delivery_id
	/* to add date constraint */
	GROUP BY DL.product_id

 UNION
	/* Transfer in lines */
SELECT TL.product_id as product_id, SUM( (  TL.qty_exp * product_purchased.inventory_qty) + TL.qty + ( TL.qty_use / product_purchased.usage_qty) ) as qty FROM public.transfer_line TL
	LEFT JOIN public.product_purchased product_purchased ON product_purchased.id = TL.product_id
	LEFT JOIN public.transfer T ON T.id = TL.transfer_id
	/* to add date constraint */
	WHERE T.type = 'transfer_in'
	GROUP BY TL.product_id

) _ENTREE GROUP BY _ENTREE.product_id ) ENTREE ON ENTREE.product_id = PP.id


/* Sortie */
LEFT JOIN
(
SELECT _SORTIE.product_id, SUM(_SORTIE.qty) as qty FROM
(

	/* Transfer out lines */
SELECT TL.product_id as product_id, SUM( ( TL.qty_exp * product_purchased.inventory_qty) +  TL.qty + ( TL.qty_use / product_purchased.usage_qty) ) as qty FROM public.transfer_line TL
	LEFT JOIN public.product_purchased product_purchased ON product_purchased.id = TL.product_id
	LEFT JOIN public.transfer T ON T.id = TL.transfer_id
	/* to add date constraint */
	WHERE T.type = 'transfer_out'
	GROUP BY TL.product_id

UNION

       /* returns qtys */
	SELECT RL.product_id as product_id, SUM( (RL.qty_exp * product_purchased.inventory_qty) + RL.qty + (RL.qty_use / product_purchased.usage_qty) ) as qty FROM public.return_line RL
	LEFT JOIN public.product_purchased product_purchased ON product_purchased.id = RL.product_id
	LEFT JOIN public.returns R ON R.id = RL.return_id
	/* add date constraint */
	GROUP BY RL.product_id

) _SORTIE GROUP BY _SORTIE.product_id) SORTIE ON SORTIE.product_id = PP.id

/* Ventes */
LEFT JOIN
(
	SELECT UNION_RESULT.product_id, SUM(UNION_RESULT.qty) as qty FROM
	(
	/* transformed products */
	(SELECT product_purchased.id as product_id, SUM( (TL.qty * RL.qty) / product_purchased.usage_qty) as qty
	FROM public.ticket_line TL
	LEFT JOIN public.ticket ticket ON TL.ticket_id = ticket.id
	LEFT JOIN public.solding_canal SC ON sc.label = ticket.destination
	LEFT JOIN public.product_sold product_sold ON product_sold.code_plu = TL.plu
	LEFT JOIN public.recipe recipe ON recipe.product_sold_id = product_sold.id AND recipe.solding_canal_id = SC.id
	LEFT JOIN public.recipe_line RL ON RL.recipe_id = recipe.id
	LEFT JOIN public.product_purchased product_purchased ON product_purchased.id = RL.product_purchased_id
	WHERE  product_sold.product_purchased_id IS NULL AND ( ticket.date >= '2016-03-08 00:00:00' AND ticket.date < '2016-03-25 00:00:00')
	GROUP BY product_purchased.id)
UNION
	/* non transformed products */
	(SELECT product_purchased.id as product_id, SUM(TL.qty / product_purchased.usage_qty) as qty
	FROM public.ticket_line TL
	LEFT JOIN public.ticket T ON TL.ticket_id = T.id
	LEFT JOIN public.product_sold product_sold ON product_sold.id = TL.product
	LEFT JOIN public.product_purchased product_purchased ON product_purchased.id = product_sold.product_purchased_id
	WHERE  product_sold.product_purchased_id IS NOT NULL AND ( T.endDate >= '2016-03-08 00:00:00' AND T.endDate < '2016-03-25 00:00:00')
	GROUP BY product_purchased.id)
	) UNION_RESULT GROUP BY product_id
) VENTES ON VENTES.product_id = PP.id

/* PERTES */
/* Item vtes */
LEFT JOIN
(SELECT RL.product_purchased_id as product_id, SUM( LL.total_loss * RL.qty / product_purchased.usage_qty ) as qty
FROM public.loss_line LL
LEFT JOIN public.loss_sheet LS ON LL.loss_sheet_id = LS.id
LEFT JOIN public.recipe recipe ON recipe.id = LL.recipe_id
LEFT JOIN public.recipe_line RL ON RL.recipe_id = recipe.id
LEFT JOIN public.product_purchased product_purchased ON RL.product_purchased_id = product_purchased.id
WHERE LS.type = 'finalProduct' AND ( LS.entry >= '2016-03-08 00:00:00' AND LS.entry < '2016-03-25 00:00:00')
GROUP BY RL.product_purchased_id) ITEM_VTES ON ITEM_VTES.product_id = PP.id

/* Pertes Item Inv */
LEFT JOIN
(SELECT LL.product_id as product_id, SUM(LL.total_loss) as qty FROM public.loss_line LL
LEFT JOIN public.loss_sheet LS ON LL.loss_sheet_id = LS.id
WHERE LS.type = 'article' AND ( LS.entry >= '2016-03-08 00:00:00' AND LS.entry < '2016-03-25 00:00:00')
GROUP BY LL.product_id
) ITEM_INV ON ITEM_INV.product_id = PP.id
) PORTION_CONTROL
WHERE ( PORTION_CONTROL.product_status = 'active' OR PORTION_CONTROL.product_status = 'toInactive')
ORDER BY PORTION_CONTROL.order