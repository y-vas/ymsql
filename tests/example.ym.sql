SELECT {
{ count(*) as `num` ::count }
{ g.`id`  ::_id }
{ group_concat(g.`id`) as id ::_cid }
{ ::user_info
  g.* , u.name as user_name
}{ ::invitations
  g.*
  , g.invitations as invited to explode
  , members AS users to explode
}{ ::search
  g.id , concat(u.alias,'/',g.name) as name
}
}~>{ g.* } FROM `Groups` g
{ INNER JOIN Users u on u.id = g.user ::user_info::search }
WHERE TRUE
{ AND FIND_IN_SET( g.`id` , s:ids ) }
{ AND ( g.`user` = i:memeber
  OR FIND_IN_SET( i:memeber , g.`members` )
)
}
{ AND concat( u.alias,'/',g.name ) LIKE '%{:search}%' }
{ AND FIND_IN_SET( i:is_invited , g.`invitations` ) }
{ AND FIND_IN_SET( g.`name` , s:names ) }
{ AND g.`date`    = d:date     }
{ AND g.`members` = s:members  }
{ AND g.`meta`    = j:meta     }

{ AND ( REPLACE(IFNULL(JSON_EXTRACT(
  IF( g.meta='' or g.meta is null , '\"\"' , g.meta )
    , concat( '$.options._' , i:memeber , '.status' )
  ) , g.status ),'\"','') = i:mixed_status
    { OR g.`name` = s:__append }
)}

{ AND g.`status`    = i:status   }
{ ORDER BY :order }
{ LIMIT r:limit { OFFSET r:offset }}
