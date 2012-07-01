var 
    u,
    a,
    d=document,
    w=window,
    f = '\$PageUrl?action=savethepage',
    l = d.location,
    en = encodeURIComponent;
u = f + '&url=' + en(l.href);
a = function () {
    if (!w.open(u, 't',
		'toolbar=0,resizable=1,scrollbars=1,status=1,width=720,height=570'))
	l.href = u;
};
if (/Firefox/.test(navigator.userAgent))
    setTimeout(a, 0);
else
    a();
void(0)