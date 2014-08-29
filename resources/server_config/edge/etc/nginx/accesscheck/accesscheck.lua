local recordingid = nil
do
  -- block a lexikalis scope miatt, hogy ne leakeljen a matches foloslegesen
  local regex = [[^/(?:devvsqlive|vsqlive|devvsq|vsq)/.*:\d+/(\d+)/\d+.*\..*$]]
  -- jo = regex options: jit, compile only once
  local matches, err = ngx.re.match( ngx.var.request_uri, regex, 'jo')
  if matches == nil then
    -- varatlan url, automatan engedjuk
    ngx.log(ngx.DEBUG, ngx.var.request_uri, " unrecognized url, allow")
    return ngx.exit(ngx.OK)
  end

  recordingid = matches[1]
end

local sessionid = ngx.var.cookie_PHPSESSID
if not sessionid or sessionid == '' then
  -- nincs sessionid, nincs mit ellenorizni
  ngx.log(ngx.DEBUG, ngx.var.request_uri, " no sessionid, forbid")
  return ngx.exit(ngx.HTTP_FORBIDDEN)
end

local secure   = ngx.var.scheme == 'https' and '1' or '0'
local cachekey = sessionid .. '_' .. recordingid .. '_' .. secure
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
if not result then
  -- nincs cacheben, meghivjuk es eltesszuk
  local options  = {['args'] = {['sessionid'] = cachekey}}
  local response = ngx.location.capture( ngx.var.accesscheckuri, options )
  if response.status ~= ngx.HTTP_OK then
    ngx.log(ngx.ERR, 'non-200 response from accesscheck! status: ', response.status, ' body: ', response.body )
    return ngx.exit(ngx.HTTP_INTERNAL_SERVER_ERROR)
  end

  local ok, err = redis:setex( cachekey, ngx.var.cacheexpirationsec, response.body )
  if not ok then
    ngx.log(ngx.ERR, 'unable to set redis key, continuing ', cachekey, " err: ", err)
  end

  result = response.body
end

local ret = nil
if result and result == '1' then
  -- nem HTTP_OK mert akkor elakad a request nalunk es nem megy tovabb a proxy-ra
  ret = ngx.OK
else
  ret = ngx.HTTP_FORBIDDEN
end

ngx.log(ngx.DEBUG, ngx.var.request_uri, " result was ", result)
-- vissza a connection poolba, 10sec idle time-al, max 100 kapcsolattal
redis:set_keepalive(10000, 100)
ngx.exit(ret)
