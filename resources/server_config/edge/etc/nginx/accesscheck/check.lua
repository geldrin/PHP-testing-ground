local sessionid = ngx.var.sessionid
if not ngx.re.find( sessionid, [[^\d+_[a-z0-9]+_\d+$]], 'jo') then
  -- nem megfelelo sessionid param, default nem engedjuk
  ngx.log(ngx.DEBUG, ngx.var.uri, ' sessionid invalid, forbid: ', sessionid )
  return ngx.exit(ngx.HTTP_FORBIDDEN)
end
ngx.log(ngx.DEBUG, ngx.var.uri, ' checking if sessionid has access: ', sessionid )

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

  ngx.log(ngx.DEBUG, ngx.var.uri, " url response was: ", response.body)
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

ngx.log(ngx.DEBUG, ngx.var.uri, " check result was ", result)
-- vissza a connection poolba, 10sec idle time-al, max 100 kapcsolattal
redis:set_keepalive(10000, 100)
ngx.exit(ret)
