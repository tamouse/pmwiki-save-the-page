(function() {var 
    u,
    a,
    d=document,
    w=window,
    f = '\$PageUrl?action=savethepage',
    l = d.location,
    en = encodeURIComponent;
u = f + '&url=' + en(l.href);
a = function () {
    if (!w.open(u, '_blank'))
	l.href = u;
};
if (/Firefox/.test(navigator.userAgent))
    setTimeout(a, 0);
else
    a();
})();
