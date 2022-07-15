   SELECT  g.*  FROM `Groups` g

WHERE TRUE

 AND ( g.`user` = 3
  OR FIND_IN_SET( 3 , g.`members` )
)








 AND ( REPLACE(IFNULL(JSON_EXTRACT(
  IF( g.meta='' or g.meta is null , '\"\"' , g.meta )
    , concat( '$.options._' , 3 , '.status' )
  ) , g.status ),'\"','') = 3
     OR g.`name` = 'xname' 
)




   