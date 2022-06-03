SELECT {
  { count(*) as `num` ::count }
  { `key` , if(json_valid(`value`),
      JSON_UNQUOTE(JSON_EXTRACT( `value` , concat( '$.' , s:lang )))
    ,`key` ) as lang
  }
}~>{ * , value as value to json } FROM `Dictionaries` WHERE TRUE
{ AND `id`     = r:id       }
{ AND `key`    = s:key      }
{ AND FIND_IN_SET(`group` , s:groups )}
{ AND `group`  = r:group    }
{ AND `links`  = s:links    }
{ AND `exclude`= s:exclude  }
{ AND `meta`   = s:meta     }
{ AND ( `value` LIKE '%{:value}%' OR `key` LIKE '%{:value}%' ) }
{ ORDER BY      :order             }
{ LIMIT i:limit { OFFSET i:offset }}
