#/opt/local/bin/perl -w


use SOAP::Lite;
use HTTP::Cookies;
use Digest::MD5 qw(md5_hex);

use Data::Dumper;

  my $pass = md5_hex('redalert');

  my $result;
  my $service = SOAP::Lite->service('http://127.0.0.1/GLE-1.2/main/htdocs/Babel_WS/connect.wsdl');
 eval {  $result = $service->WSconnect('eos',$pass) };
 my $secret = $result;
 if ($@) {
     die $@;
 }


# $client = new SoapClient("http://127.0.0.1/GLE-1.2/main/htdocs/Babel_WS/stockquote.wsdl");
#            $client->__setCookie ( $cookieName, $secret);
#            //$client->connect('eos');
#
#            $return = $client->getItemCount('12345');
 my $cookie_jar = HTTP::Cookies->new();
 my $expire = time() + 3600;
    $cookie_jar->set_cookie( 1, "GleWSCookie", $secret, "/", "127.0.0.1", 80, 0, 0, $expire );

  my $service1 = SOAP::Lite
                          ->proxy('http://127.0.0.1/GLE-1.2/main/htdocs/Babel_WS/webservices.php', cookie_jar => $cookie_jar)
                          #->service('http://127.0.0.1/GLE-1.2/main/htdocs/Babel_WS/stockquote.wsdl')
                          ;
 eval {  $result = $service1->listSocWithContract()->result() };
 if ($@) {
     die $@;
 }

 my @arr;
 @arr = split(/,/,$result);
 print Dumper(@arr);

#
#  $service = SOAP::Lite -> uri('http://127.0.0.1/GLE-1.2/main/htdocs/Babel_WS/webservices.php')
#               -> proxy('http://127.0.0.1/GLE-1.2/main/htdocs/Babel_WS/webservices.php');
#  $result = $service -> getItemCount('12345') -> result();
#  print $result->faultstring;
