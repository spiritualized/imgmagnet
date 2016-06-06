
luasql = require("luasql.mysql")

function split(pString, pPattern)
	local Table = {}  -- NOTE: use {n = 0} in Lua-5.0
	local fpat = "(.-)" .. pPattern
	local last_end = 1
	local s, e, cap = pString:find(fpat, 1)
	
	while s do
		if s ~= 1 or cap ~= "" then
			table.insert(Table,cap)
		end
	
		last_end = e+1
		s, e, cap = pString:find(fpat, last_end)
	end
	
	if last_end <= #pString then
		cap = pString:sub(last_end)
		table.insert(Table, cap)
	end
	return Table
end

function file_exists(name)
   local f=io.open(name,"r")
   if f~=nil then io.close(f) return true else return false end
end

function setup_sql()
	MySQL = luasql.mysql()
    	con = MySQL:connect(
             "imghost"
            ,"imghost"
            ,""
            ,"localhost"
        )
end

function cleanup_sql()
	con:close()
	MySQL:close()
end

function addview(filename, cached)
	if cached == true then
		con:execute(string.format([[
                    UPDATE uploads SET views_cached = views_cached + 1, last_viewed = unix_timestamp() WHERE filename = '%s';
                    ]], filename)
                )
	else

	    con:execute(string.format([[
        	    UPDATE uploads SET views = views + 1, last_viewed = unix_timestamp() WHERE filename = '%s';
        	    ]], filename)
	        )
	end

end

function getinfo(filename)
	local cur = con:execute(string.format([[
                    SELECT uploaded, mime FROM uploads WHERE filename = '%s';
                    ]], filename)
                )

	local info = {}

	if cur:numrows() == 0 then
		info["uploaded"] = 0
		info["mime"] = "image/png"
	else
        	local row = cur:fetch({}, "a")
		info["uploaded"] = row.uploaded
		info["mime"] = row.mime
	end

	return info
end

filename = split(lighty.env["uri.path-raw"], "/")[1]
path = "/imghost/images/"
fullpath = path..filename

if file_exists(fullpath) == false then
        fullpath = "/imghost/www/placeholder.png"
end

cached = false

setup_sql()

info = getinfo(filename)

if lighty.request["If-Modified-Since"] == os.date("%a, %d %b %Y %h:%M:%S GMT", info["uploaded"]) then
	cached = true
end

addview(filename, cached)

cleanup_sql()

if cached == true then
	return 304
else

	lighty.content = { { filename = fullpath } }
	lighty.header["Content-Type"] = info["mime"]
	lighty.header["Cache-Control"] = "private, max-age=31536000"
	lighty.header["Expires"] = os.date("%a, %d %b %Y %h:%M:%S GMT", os.time() + 86400*365)
	lighty.header["Last-Modified"] = os.date("%a, %d %b %Y %h:%M:%S GMT", info["uploaded"])
	--lighty.header["Content-Type"] = "text/html"

	return 200
end

