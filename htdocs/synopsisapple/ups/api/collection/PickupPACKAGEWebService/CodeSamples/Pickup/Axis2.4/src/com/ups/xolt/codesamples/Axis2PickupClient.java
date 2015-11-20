/* 
 ** 
 ** Filename: Axis2PickupClient.java 
 ** Authors: United Parcel Service of America
 ** 
 ** The use, disclosure, reproduction, modification, transfer, or transmittal 
 ** of this work for any purpose in any form or by any means without the 
 ** written permission of United Parcel Service is strictly prohibited. 
 ** 
 ** Confidential, Unpublished Property of United Parcel Service. 
 ** Use and Distribution Limited Solely to Authorized Personnel. 
 ** 
 ** Copyright 2009 United Parcel Service of America, Inc.  All Rights Reserved. 
 ** 
 */
package com.ups.xolt.codesamples;

import java.io.BufferedWriter;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileWriter;
import java.util.Calendar;
import java.util.Properties;

import com.ups.www.wsdl.xoltws.pickup.v1_1.PickupCreationErrorMessage;
import com.ups.www.wsdl.xoltws.pickup.v1_1.PickupServiceStub;
import com.ups.www.wsdl.xoltws.pickup.v1_1.PickupServiceStub.UsernameToken_type0;

public class Axis2PickupClient {
	
	private static String url;
	private static String accesskey;
	private static String username;
	private static String password;
	private static String out_file_location = "out_file_location";
	private static String tool_or_webservice_name = "tool_or_webservice_name";
	static Properties props = null;

	static{
        try{
        	props = new Properties();
        	props.load(new FileInputStream("./build.properties"));
	  		url = props.getProperty("url");
	  		accesskey = props.getProperty("accesskey");
	  		username = props.getProperty("username");
	  		password = props.getProperty("password");
        }
        catch(Exception e){
        	e.printStackTrace();
        }
	}
	
	public static void main(String[] args) {
		String statusCode = null;
		String description = null;
		try {
			PickupServiceStub pickupService = new PickupServiceStub(url);
			PickupServiceStub.PickupCreationRequest pickupRequest = new PickupServiceStub.PickupCreationRequest();
			PickupServiceStub.RequestType request = new PickupServiceStub.RequestType();
			String[] requestOption = { " " };
			request.setRequestOption(requestOption);
			pickupRequest.setRequest(request);

			pickupRequest.setRatePickupIndicator("N");
			
			PickupServiceStub.ShipperType shipper = new PickupServiceStub.ShipperType();
			PickupServiceStub.AccountType account = new PickupServiceStub.AccountType();
			account.setAccountCountryCode("US");
			account.setAccountNumber("1004YY");
			shipper.setAccount(account);
			pickupRequest.setShipper(shipper);
			
			PickupServiceStub.PickupDateInfoType pickupDateInfo = new PickupServiceStub.PickupDateInfoType();
			pickupDateInfo.setCloseTime("2000");
			pickupDateInfo.setPickupDate("20101117");
			pickupDateInfo.setReadyTime("0900");
			pickupRequest.setPickupDateInfo(pickupDateInfo);
			
			PickupServiceStub.PickupAddressType pickupAddress = new PickupServiceStub.PickupAddressType();
			String[] addressLine = {"315 Saddle Bridge Drive"};
			pickupAddress.setAddressLine(addressLine);
			pickupAddress.setCity("Allendale");
			pickupAddress.setCompanyName("Pickup Proxy");
			pickupAddress.setContactName("Pickup Manage");
			pickupAddress.setCountryCode("US");
			pickupAddress.setFloor("2");
			pickupAddress.setStateProvince("NJ");
			PickupServiceStub.PhoneType phoneType = new PickupServiceStub.PhoneType();
			phoneType.setExtension("911");
			phoneType.setNumber("6785851306");
			pickupAddress.setPhone(phoneType);
			pickupAddress.setPostalCode("07401");
			pickupAddress.setPickupPoint("Lobby");
			pickupAddress.setResidentialIndicator("Y");
			pickupRequest.setPickupAddress(pickupAddress);
			pickupRequest.setAlternateAddressIndicator("N");
			
			PickupServiceStub.PickupPieceType[] pickupPiece = new PickupServiceStub.PickupPieceType[1];
			PickupServiceStub.PickupPieceType pickupType = new PickupServiceStub.PickupPieceType();
			pickupType.setContainerCode("01");
			pickupType.setDestinationCountryCode("US");
			pickupType.setQuantity("2");
			pickupType.setServiceCode("002");
			pickupPiece[0] = pickupType;
			pickupRequest.setPickupPiece(pickupPiece);
			
			PickupServiceStub.WeightType totalWeight = new PickupServiceStub.WeightType();
			totalWeight.setUnitOfMeasurement("LBS");
			totalWeight.setWeight("5.5");
			pickupRequest.setTotalWeight(totalWeight);
			pickupRequest.setOverweightIndicator("N"); 
			pickupRequest.setPaymentMethod("01");
			pickupRequest.setSpecialInstruction("Don't Squeeze the Charmin, PLEASE!!!!");  
			
			/** ************UPSSE************************** */
			PickupServiceStub.UPSSecurity upss = new PickupServiceStub.UPSSecurity();
			PickupServiceStub.ServiceAccessToken_type0 upsSvcToken = new PickupServiceStub.ServiceAccessToken_type0();
			upsSvcToken.setAccessLicenseNumber(accesskey);
			upss.setServiceAccessToken(upsSvcToken);
			UsernameToken_type0 upsSecUsrnameToken = new UsernameToken_type0();
			upsSecUsrnameToken.setUsername(username);
			upsSecUsrnameToken.setPassword(password);
			upss.setUsernameToken(upsSecUsrnameToken);
			/** ************UPSSE***************************** */
			PickupServiceStub.PickupCreationResponse pickupResponse = pickupService.ProcessPickupCreation(pickupRequest, upss);
			statusCode = pickupResponse.getResponse().getResponseStatus().getCode();
			description = pickupResponse.getResponse().getResponseStatus().getDescription();
			updateResultsToFile(statusCode, description);
			System.out.println("The transaction was a " + pickupResponse.getResponse().getResponseStatus().getDescription());
			
		} catch (Exception e) {
			description=e.getMessage();
			statusCode=e.toString();
			if (e instanceof PickupCreationErrorMessage){
				PickupCreationErrorMessage pickupErr = (PickupCreationErrorMessage)e;
				System.out.print("Receieved Error "+pickupErr.getFaultMessage().getErrorDetail()[0].getPrimaryErrorCode().getCode()+" ");
				System.out.println(pickupErr.getFaultMessage().getErrorDetail()[0].getPrimaryErrorCode().getDescription());
				description=pickupErr.getFaultMessage().getErrorDetail()[0].getPrimaryErrorCode().getDescription();
				statusCode=pickupErr.getFaultMessage().getErrorDetail()[0].getPrimaryErrorCode().getCode();
			}
			 else{
				 e.printStackTrace();
			 }
			updateResultsToFile(statusCode, description);
		}
	}
	/**
     * This method updates the XOLTResult.xml file with the received status and description
     * @param statusCode
     * @param description
     */
	private static void updateResultsToFile(String statusCode, String description){
    	BufferedWriter bw = null;
    	try{    		
    		
    		File outFile = new File(props.getProperty(out_file_location));
    		System.out.println("Output file deletion status: " + outFile.delete());
    		outFile.createNewFile();
    		System.out.println("Output file location: " + outFile.getCanonicalPath());
    		bw = new BufferedWriter(new FileWriter(outFile));
    		StringBuffer strBuf = new StringBuffer();
    		strBuf.append("<ExecutionAt>");
    		strBuf.append(Calendar.getInstance().getTime());
    		strBuf.append("</ExecutionAt>\n");
    		strBuf.append("<ToolOrWebServiceName>");
    		strBuf.append(props.getProperty(tool_or_webservice_name));
    		strBuf.append("</ToolOrWebServiceName>\n");
    		strBuf.append("\n");
    		strBuf.append("<ResponseStatus>\n");
    		strBuf.append("\t<Code>");
    		strBuf.append(statusCode);
    		strBuf.append("</Code>\n");
    		strBuf.append("\t<Description>");
    		strBuf.append(description);
    		strBuf.append("</Description>\n");
    		strBuf.append("</ResponseStatus>");
    		bw.write(strBuf.toString());
    		bw.close();    		    		
    	}catch (Exception e) {
			e.printStackTrace();
		}finally{
			try{
				if (bw != null){
					bw.close();
					bw = null;
				}
			}catch (Exception e) {
				e.printStackTrace();
			}			
		}		
    }


}
