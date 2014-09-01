-- a parametereket strippeljuk a cachelhetoseg kedveert
ngx.req.set_uri_args('')

-- a HDS-nel a parameterek base64 encodolva jonnek a filenevben, ezt is ki kell pucolni
-- itt peldaul a "client=1" param van atadva, a _q a prefix
-- /devvsq/_definst_/mp4:105/105/105_295_video_360p.mp4/media_b306792_qY2xpZW50PTE=.abst/Seg1-Frag1
local regex  = [[^(.*)(?:_q[a-zA-Z0-9=+/]+)(\.abst/Seg[0-9]+-Frag[0-9]+.*)$]]
local newuri = ngx.re.sub( ngx.var.request_uri, regex, 'jo')
if newuri then
  ngx.log(ngx.DEBUG, "rewritten url from: ", ngx.var.request_uri, " to: ", newuri )
  ngx.req.set_uri( newuri )
end
