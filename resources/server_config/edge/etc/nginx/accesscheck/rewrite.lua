-- a parametereket strippeljuk a cachelhetoseg kedveert mindig
do
  local arguments = ngx.req.get_uri_args(2)
  if arguments.sessionid then
    ngx.var.sessionid = arguments.sessionid
    ngx.log(ngx.DEBUG, ngx.var.request_uri, ' sessionid param found, OK')
    ngx.req.set_uri_args('')
    return ngx.exit(ngx.OK)
  end
end

-- a HDS-nel a parameterek base64 encodolva jonnek a filenevben, ezt is ki kell pucolni
-- itt peldaul a 'client=1' param van atadva, a _q a prefix
-- /devvsq/_definst_/mp4:105/105/105_295_video_360p.mp4/media_b306792_qY2xpZW50PTE=.abst/Seg1-Frag1
local regex   = [[^(.+)(?:_q([a-zA-Z0-9=+/]+))(\.abst/Seg[0-9]+-Frag[0-9]+)$]]
local matches = ngx.re.match( ngx.var.request_uri, regex, 'jo')
if not matches then
  ngx.log(ngx.DEBUG, ngx.var.request_uri, ' unknown url, forbid')
  return ngx.exit(ngx.HTTP_FORBIDDEN)
end

ngx.var.args    = ngx.decode_base64( matches[2] )
local arguments = ngx.req.get_uri_args(2)
if not arguments.sessionid then
  -- nincs sessionid param, default nem engedjuk
  ngx.log(ngx.DEBUG, ngx.var.request_uri, ' no sessionid in encoded filename found, forbid')
  return ngx.exit(ngx.HTTP_FORBIDDEN)
end
ngx.var.sessionid = arguments.sessionid
ngx.req.set_uri_args('')

local newuri = matches[1] .. matches[3]
ngx.log(ngx.DEBUG, 'rewritten url from: ', ngx.var.request_uri, ' to: ', newuri )
ngx.req.set_uri( newuri )
ngx.var.cachekey = cachekey
