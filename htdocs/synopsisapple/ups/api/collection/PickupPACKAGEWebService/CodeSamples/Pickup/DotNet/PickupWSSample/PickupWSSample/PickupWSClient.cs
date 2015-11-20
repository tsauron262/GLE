using System;
using System.Collections.Generic;
using System.Text;
using PickupWSSample.PickupWebReference;
using System.ServiceModel;

namespace PickupWSSample
{
    class PickupClient
    {
        static void Main()
        {
            try
            {
                PickupService pickupService = new PickupService();
                PickupCreationRequest pickupCreationRequest = new PickupCreationRequest();
                RequestType request = new RequestType();
                String[] requestOption = { " " };
                request.RequestOption = requestOption;
                pickupCreationRequest.Request = request;
                pickupCreationRequest.RatePickupIndicator = "N";

                ShipperType shipper = new ShipperType();
                AccountType account = new AccountType();
                account.AccountCountryCode = "US";
                account.AccountNumber = "1004YY";
                shipper.Account = account;
                pickupCreationRequest.Shipper = shipper;

                PickupDateInfoType pickupDateInfo = new PickupDateInfoType();
                pickupDateInfo.CloseTime = "2000";
                pickupDateInfo.PickupDate = "20091230";
                pickupDateInfo.ReadyTime = "0900";
                pickupCreationRequest.PickupDateInfo = pickupDateInfo;

                PickupAddressType pickupAddress = new PickupAddressType();
                String[] addressLine = { "315 Saddle Bridge Drive" };
                pickupAddress.AddressLine = addressLine;
                pickupAddress.City = "Allendale";
                pickupAddress.CompanyName = "Pickup Proxy";
                pickupAddress.ContactName = "Pickup Proxy";
                pickupAddress.CountryCode = "US";
                pickupAddress.Floor = "2";
                pickupAddress.StateProvince = "NJ";

                PhoneType phoneType = new PhoneType();
                phoneType.Extension = "911";
                phoneType.Number = "6785851399";
                pickupAddress.Phone = phoneType;
                pickupAddress.PostalCode = "07401";
                pickupAddress.PickupPoint = "Lobby";
                pickupAddress.ResidentialIndicator = "Y";

                pickupCreationRequest.PickupAddress = pickupAddress;
                pickupCreationRequest.AlternateAddressIndicator = "N";

                PickupPieceType[] pickupPiece = new PickupPieceType[1];
                PickupPieceType pickupType = new PickupPieceType();
                pickupType.ContainerCode = "01";
                pickupType.DestinationCountryCode = "US";
                pickupType.Quantity = "27";
                pickupType.ServiceCode = "002";
                pickupPiece[0] = pickupType;
                pickupCreationRequest.PickupPiece = pickupPiece;

                WeightType totalWeight = new WeightType();                
                totalWeight.UnitOfMeasurement = "LBS";
                totalWeight.Weight = "2.0";

                pickupCreationRequest.TotalWeight = totalWeight;
                pickupCreationRequest.OverweightIndicator = "N";

                //String[] returnTrackingNumber = { "Your return tracking number 1", "Your return tracking number 2", "Your return tracking number 3" };
                //pickupCreationRequest.ReturnTrackingNumber = returnTrackingNumber;
                pickupCreationRequest.PaymentMethod = "01";
                pickupCreationRequest.SpecialInstruction = ".Net Sample code for Pickup Client";

                

                UPSSecurity upss = new UPSSecurity();
                UPSSecurityServiceAccessToken upssSvcAccessToken = new UPSSecurityServiceAccessToken();
                upssSvcAccessToken.AccessLicenseNumber = "EC408514586D9EA8";
                upss.ServiceAccessToken = upssSvcAccessToken;
                UPSSecurityUsernameToken upssUsrNameToken = new UPSSecurityUsernameToken();
                upssUsrNameToken.Username = "JCOLEV";
                upssUsrNameToken.Password = "JCOLEV";
                upss.UsernameToken = upssUsrNameToken;
                pickupService.UPSSecurityValue = upss;

                System.Net.ServicePointManager.CertificatePolicy = new TrustAllCertificatePolicy();
                Console.WriteLine(pickupCreationRequest);
                PickupCreationResponse pickupCreationResponse = pickupService.ProcessPickupCreation(pickupCreationRequest);
                Console.WriteLine("The transaction was a " + pickupCreationResponse.Response.ResponseStatus.Description);
                Console.WriteLine("The Pickup Request Confirmation Number is  : " + pickupCreationResponse.PRN);
                Console.ReadKey();
            }
            catch (System.Web.Services.Protocols.SoapException ex)
            {
                Console.WriteLine("");
                Console.WriteLine("---------Pickup Web Service returns error----------------");
                Console.WriteLine("---------\"Hard\" is user error \"Transient\" is system error----------------");
                Console.WriteLine("SoapException Message= " + ex.Message);
                Console.WriteLine("");
                Console.WriteLine("SoapException Category:Code:Message= " + ex.Detail.LastChild.InnerText);
                Console.WriteLine("");
                Console.WriteLine("SoapException XML String for all= " + ex.Detail.LastChild.OuterXml);
                Console.WriteLine("");
                Console.WriteLine("SoapException StackTrace= " + ex.StackTrace);
                Console.WriteLine("-------------------------");
                Console.WriteLine("");
            }
            catch (System.ServiceModel.CommunicationException ex)
            {
                Console.WriteLine("");
                Console.WriteLine("--------------------");
                Console.WriteLine("CommunicationException= " + ex.Message);
                Console.WriteLine("CommunicationException-StackTrace= " + ex.StackTrace);
                Console.WriteLine("-------------------------");
                Console.WriteLine("");

            }
            catch (Exception ex)
            {
                Console.WriteLine("");
                Console.WriteLine("-------------------------");
                Console.WriteLine(" General Exception= " + ex.Message);
                Console.WriteLine(" General Exception-StackTrace= " + ex.StackTrace);
                Console.WriteLine("-------------------------");

            }
            finally
            {
                Console.ReadKey();
            }

        }
    }
}
