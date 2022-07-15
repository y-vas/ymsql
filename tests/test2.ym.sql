   SELECT  g.*  FROM `Groups` g

WHERE TRUE

 AND ( g.`user` = 3
  OR FIND_IN_SET( 3 , g.`members` )
)













   