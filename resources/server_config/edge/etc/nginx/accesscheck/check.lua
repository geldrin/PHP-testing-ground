local sessionid = nil
do -- block a lexikalis scope miatt, hogy ne leakeljunk foloslegesen valtozokat
  -- kizarolag HLS es HDS stream segmenseket validalunk
  -- a query paramokat a rewrite strippelheti, ezert captureoljuk itt
  -- a HDS-nel a parameterek base64 encodolva jonnek a filenevben, ezt is ki kell pucolni pl:
  -- /devvsq/_definst_/mp4:105/105/105_295_video_360p.mp4/media_b306792_qY2xpZW50PTE=.abst/Seg1-Frag1
  local regex = [[^/(?:devvsqlive|vsqlive|devvsq|vsq)/.*:\d+/\d+/\d+.*\..*/(?:media_\d+.ts|(?:_q([a-zA-Z0-9=+/])+\.abst/)?Seg[0-9]+-Frag[0-9]+.*).*?(?:\?(.*))?$]]
  -- jo = regex options: jit, compile only once
  local matches = ngx.re.match( ngx.var.request_uri, regex, 'jo')
  if not matches then
    -- varatlan url, automatan engedjuk
    ngx.log(ngx.DEBUG, ngx.var.request_uri, " unrecognized url, allow")
    -- nem HTTP_OK mert akkor elakad a request nalunk es nem megy tovabb a proxy-ra
    return ngx.exit(ngx.OK)
  end

  if not matches[1] and not matches[2] then
    -- nincs sessionid param, default nem engedjuk
    ngx.log(ngx.DEBUG, ngx.var.request_uri, " no query/encoded params, forbid")
    return ngx.exit(ngx.HTTP_FORBIDDEN)
  end

  local origargs  = ngx.var.args
  if matches[1] then
    ngx.var.args = ngx.decode_base64( matches[1] )
  elseif matches[2] then
    ngx.var.args = matches[2]
  end

  local arguments = ngx.req.get_uri_args(2)
  if not arguments.sessionid then
    -- nincs sessionid param, default nem engedjuk
    ngx.log(ngx.DEBUG, ngx.var.request_uri, " no sessionid in query params, forbid")
    return ngx.exit(ngx.HTTP_FORBIDDEN)
  end

  sessionid    = arguments.sessionid
  ngx.var.args = origargs
end

local cachekey = sessionid
local redis    = (require 'resty.redis'):new()

redis:set_timeout(1000)
local ok, err = redis:connect( ngx.var.redis_host, tonumber( ngx.var.redis_port ) )
if not ok then
  ngx.log(ngx.ERR, 'redis connect failed ', err)
  return ngx.exit(ngx.HTTP_INTERNAL_SERVER_ERROR)
end

local ok, err = redis:select( tonumber( ngx.var.redis_db ) )
if not ok then
  ngx.log(ngx.ERR, 'could not select redis db ', err )
  return ngx.exit(ngx.HTTP_INTERNAL_SERVER_ERROR)
end

local result = redis:get( cachekey )
if not result or result == ngx.null then
  -- nincs cacheben, meghivjuk es eltesszuk
  -- ezek internal location-ok mert nem tudunk tetszes szerinti urlt meghivni magunk
  local checkuri = ngx.var.scheme == 'https' and "/secureaccesscheck" or "/accesscheck"
  local response = ngx.location.capture( checkuri, {['args'] = {['sessionid'] = sessionid}} )

  if response.status ~= ngx.HTTP_OK then
    ngx.log(ngx.ERR, 'non-200 response from accesscheck! status: ', response.status, ' body: ', response.body )
    return ngx.exit(ngx.HTTP_INTERNAL_SERVER_ERROR)
  end

  ngx.log(ngx.DEBUG, ngx.var.request_uri, " accesscheck response was: ", response.body)
  local matched = ngx.re.find( response.body, [[<success>1</success>]], 'jo' )
  result = matched and '1' or '0'

  local ok, err = redis:setex( cachekey, ngx.var.cacheexpirationsec, result )
  if not ok then
    ngx.log(ngx.ERR, 'unable to set redis key, continuing ', cachekey, " err: ", err)
  end
end

local ret = nil
if result and result == '1' then
  ret = ngx.OK
else
  ret = ngx.HTTP_FORBIDDEN
end

ngx.log(ngx.DEBUG, ngx.var.request_uri, " result was ", result)
-- vissza a connection poolba, 10sec idle time-al, max 100 kapcsolattal
redis:set_keepalive(10000, 100)
ngx.exit(ret)
