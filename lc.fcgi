#!/usr/bin/env python

import sys, re
dist_dir = "/home/calvin/projects/linkchecker"
sys.path.insert(0,dist_dir)
import fcgi, linkcheck

# main
try:
    while fcgi.isFCGI():
        req = fcgi.FCGI()
        req.out.write("Content-type: text/html\r\n"
                      "Cache-Control: no-cache\r\n"
                      "\r\n")
        form = req.getFieldStorage()
        if not linkcheck.lc_cgi.checkform(form):
            linkcheck.lc_cgi.logit(form, req.env)
            linkcheck.lc_cgi.printError(req.out)
            req.Finish()
            continue
        config = linkcheck.Config.Configuration()
        config["recursionlevel"] = int(form["level"].value)
        config["log"] = linkcheck.Logging.HtmlLogger(req.out)
        config.disableThreading()
        if form.has_key("anchors"):    config["anchors"] = 1
        if not form.has_key("errors"): config["verbose"] = 1
        if form.has_key("intern"):
            config["internlinks"].append(re.compile("^(ftp|https?)://"+\
	    linkcheck.lc_cgi.getHostName(form)))
        else:
            config["internlinks"].append(re.compile(".+"))
        # avoid checking of local files
        config["externlinks"].append((re.compile("^file:"), 1))
        # start checking
        config.appendUrl(linkcheck.UrlData.GetUrlDataFrom(form["url"].value, 0))
        linkcheck.checkUrls(config)
        req.Finish()
except:
    import traceback
    traceback.print_exc(file = open('traceback', 'a'))

