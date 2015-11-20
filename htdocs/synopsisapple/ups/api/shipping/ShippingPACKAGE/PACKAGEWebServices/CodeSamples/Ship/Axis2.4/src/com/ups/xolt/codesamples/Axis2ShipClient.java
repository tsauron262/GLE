/* 
 ** 
 ** Filename: Axis2ShipClient.java 
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
import java.io.IOException;
import java.util.Calendar;
import java.util.Properties;

import com.ups.www.wsdl.xoltws.ship.v1_0.ShipServiceStub;
import com.ups.www.wsdl.xoltws.ship.v1_0.ShipmentErrorMessage;
import com.ups.www.wsdl.xoltws.ship.v1_0.ShipServiceStub.BillShipperType;
import com.ups.www.wsdl.xoltws.ship.v1_0.ShipServiceStub.InternationalFormType;
import com.ups.www.wsdl.xoltws.ship.v1_0.ShipServiceStub.LabelImageFormatType;
import com.ups.www.wsdl.xoltws.ship.v1_0.ShipServiceStub.LabelSpecificationType;
import com.ups.www.wsdl.xoltws.ship.v1_0.ShipServiceStub.PackageType;
import com.ups.www.wsdl.xoltws.ship.v1_0.ShipServiceStub.PackageWeightType;
import com.ups.www.wsdl.xoltws.ship.v1_0.ShipServiceStub.PackagingType;
import com.ups.www.wsdl.xoltws.ship.v1_0.ShipServiceStub.PaymentInfoType;
import com.ups.www.wsdl.xoltws.ship.v1_0.ShipServiceStub.ProductType;
import com.ups.www.wsdl.xoltws.ship.v1_0.ShipServiceStub.ProductWeightType;
import com.ups.www.wsdl.xoltws.ship.v1_0.ShipServiceStub.RequestType;
import com.ups.www.wsdl.xoltws.ship.v1_0.ShipServiceStub.ServiceAccessToken_type0;
import com.ups.www.wsdl.xoltws.ship.v1_0.ShipServiceStub.ServiceType;
import com.ups.www.wsdl.xoltws.ship.v1_0.ShipServiceStub.ShipAddressType;
import com.ups.www.wsdl.xoltws.ship.v1_0.ShipServiceStub.ShipFromType;
import com.ups.www.wsdl.xoltws.ship.v1_0.ShipServiceStub.ShipPhoneType;
import com.ups.www.wsdl.xoltws.ship.v1_0.ShipServiceStub.ShipToAddressType;
import com.ups.www.wsdl.xoltws.ship.v1_0.ShipServiceStub.ShipToType;
import com.ups.www.wsdl.xoltws.ship.v1_0.ShipServiceStub.ShipUnitOfMeasurementType;
import com.ups.www.wsdl.xoltws.ship.v1_0.ShipServiceStub.ShipmentChargeType;
import com.ups.www.wsdl.xoltws.ship.v1_0.ShipServiceStub.ShipmentRequest;
import com.ups.www.wsdl.xoltws.ship.v1_0.ShipServiceStub.ShipmentResponse;
import com.ups.www.wsdl.xoltws.ship.v1_0.ShipServiceStub.ShipmentServiceOptionsType;
import com.ups.www.wsdl.xoltws.ship.v1_0.ShipServiceStub.ShipmentType;
import com.ups.www.wsdl.xoltws.ship.v1_0.ShipServiceStub.ShipperType;
import com.ups.www.wsdl.xoltws.ship.v1_0.ShipServiceStub.TransactionReferenceType;
import com.ups.www.wsdl.xoltws.ship.v1_0.ShipServiceStub.UPSSecurity;
import com.ups.www.wsdl.xoltws.ship.v1_0.ShipServiceStub.UnitOfMeasurementType;
import com.ups.www.wsdl.xoltws.ship.v1_0.ShipServiceStub.UsernameToken_type0;

public class Axis2ShipClient {
	private static String url;
	private static String accessKey;
	private static String userName;
	private static String password;
	private static String buildPropertiesPath="./build.properties";
	private static String out_file_location="out_file_location";
	private static String tool_or_webservice_name="tool_or_webservice_name";
    private static String statusCode = null;
	private static String description = null;
	
	private static void loadProperties(){
		Properties properties = new Properties();
		try {
			properties.load(new FileInputStream(buildPropertiesPath));
			
		} catch (IOException e) {
			statusCode = e.getMessage();
			description = e.toString();
			updateResultsToFile(statusCode, description);
    		e.printStackTrace();
		}
		
		url=properties.getProperty("url");
		accessKey=properties.getProperty("accesskey");
		userName=properties.getProperty("username");
		password=properties.getProperty("password");		
		out_file_location=properties.getProperty("out_file_location");
		tool_or_webservice_name=properties.getProperty("tool_or_webservice_name");
	}
	
	public static void main(String[] arguments) throws Exception {
		try {
			loadProperties();
			ShipServiceStub shipServiceStub = new ShipServiceStub(url);
			ShipmentRequest shipRequest = new ShipmentRequest();
			RequestType request = new RequestType();
			TransactionReferenceType transactionReference = new TransactionReferenceType();
			transactionReference.setCustomerContext("AXIS Test CLient");
			request.setTransactionReference(transactionReference);
			String[] requestOption = { "nonvalidate" };
			request.setRequestOption(requestOption);
			shipRequest.setRequest(request);			
			
			ShipmentType shpmnt = new ShipmentType();		

			/** *******Shipper******************** */
			ShipperType shipper = new ShipperType();
			shipper.setName("ABC Associates");
			shipper.setAttentionName("ABC");
			shipper.setShipperNumber("ISUS01");
			ShipPhoneType shipperPhone = new ShipPhoneType();
			shipperPhone.setNumber("9876543212");
			shipper.setPhone(shipperPhone);
			ShipAddressType shipperAddress = new ShipAddressType();
			String[] addressLines = { "480 Parkton Plaza" };
			shipperAddress.setAddressLine(addressLines);
			shipperAddress.setCity("Timonium");
			shipperAddress.setPostalCode("21093");
			shipperAddress.setStateProvinceCode("MD");
			shipperAddress.setCountryCode("US");
			shipper.setAddress(shipperAddress);
			shpmnt.setShipper(shipper);
			/********Shipper**********************/

			shpmnt.setDescription("Gifts");
			
			/**************ShipFrom*******************/
			ShipFromType shipFrom = new ShipFromType();
			shipFrom.setName("ABC Associates");
			shipFrom.setAttentionName("Mr.ABC");
			ShipAddressType shipFromAddress = new ShipAddressType();
			shipFromAddress.setAddressLine(addressLines);
			shipFromAddress.setCity("Timonium");
			shipFromAddress.setPostalCode("21093");
			shipFromAddress.setStateProvinceCode("MD");
			shipFromAddress.setCountryCode("US");
			shipFrom.setAddress(shipFromAddress);
			shpmnt.setShipFrom(shipFrom);
			/*************ShipFrom**********************/

			/**************ShipTo*******************/
			ShipToType shipTo = new ShipToType();
			shipTo.setName("DEF Associates");
			shipTo.setAttentionName("DEF");
			ShipToAddressType shipToAddress = new ShipToAddressType();
			ShipPhoneType shpPhone = new ShipPhoneType();
			shpPhone.setNumber("1234456789");
			shipTo.setPhone(shpPhone);
			String[] shipToAddressLines = { "201 York Rd" };
			shipToAddress.setAddressLine(shipToAddressLines);
			shipToAddress.setCity("Roswell");
			shipToAddress.setStateProvinceCode("GA");
			shipToAddress.setPostalCode("30076");
			shipToAddress.setCountryCode("US");
			shipTo.setAddress(shipToAddress);
			shpmnt.setShipTo(shipTo);
			/*************ShipTo********************* */

			/**********Service********************** */
			ServiceType service = new ServiceType();
			service.setCode("03");
			shpmnt.setService(service);
			/**********Service********************** */

			/********************Package***************** */
			PackageType pkg1 = new PackageType();
			PackagingType pkgingType = new PackagingType();
			pkgingType.setCode("02");
			pkg1.setPackaging(pkgingType);
			PackageType[] pkgArray = { pkg1 };
			PackageWeightType weight = new PackageWeightType();
			weight.setWeight("1");
			ShipUnitOfMeasurementType shpUnitOfMeas = new ShipUnitOfMeasurementType();
			shpUnitOfMeas.setCode("LBS");
			shpUnitOfMeas.setDescription("Pounds");
			weight.setUnitOfMeasurement(shpUnitOfMeas);
			pkg1.setPackageWeight(weight);
			shpmnt.setPackage(pkgArray);
			/********************Package***************** */

			/***************Payment Information***************** */
			PaymentInfoType payInfo = new PaymentInfoType();
			ShipmentChargeType shpmntCharge = new ShipmentChargeType();
			shpmntCharge.setType("01");
			BillShipperType billShipper = new BillShipperType();
			billShipper.setAccountNumber("ISUS01");
			shpmntCharge.setBillShipper(billShipper);
			ShipmentChargeType[] shpmntChargeArray = { shpmntCharge };
			payInfo.setShipmentCharge(shpmntChargeArray);
			shpmnt.setPaymentInformation(payInfo);
			/** *************Payment Information***************** */

			/** **********Label Specification ******************** */
			LabelSpecificationType labelSpecType = new LabelSpecificationType();
			LabelImageFormatType labelImageFormat = new LabelImageFormatType();
			labelImageFormat.setCode("GIF");
			labelImageFormat.setDescription("GIF");
			labelSpecType.setLabelImageFormat(labelImageFormat);
			labelSpecType.setHTTPUserAgent("Mozilla/4.5");
			shipRequest.setLabelSpecification(labelSpecType);
			/** ***********Label Specification********************* */
			
			/***************InternationalForms********************/
			ShipmentServiceOptionsType  shpSvcOptions = new ShipmentServiceOptionsType();
			InternationalFormType intlForms = new InternationalFormType();
			intlForms.addFormType("03");
			intlForms.setFormGroupIdName("CO Form");
			intlForms.setExportDate("20080819");
			intlForms.setExportingCarrier("funship");
			intlForms.setCurrencyCode("USD");
			ProductType product = new ProductType();
			product.addDescription("Prod 1");
			product.setOriginCountryCode("US");
			product.setMarksAndNumbers("ABC123");
			product.setNumberOfPackagesPerCommodity("6");
			ProductWeightType productWeight = new ProductWeightType();
			productWeight.setWeight("58");
			UnitOfMeasurementType uom = new UnitOfMeasurementType();
			uom.setCode("KGS");
			uom.setDescription("KiloGrams");
			productWeight.setUnitOfMeasurement(uom);
			product.setProductWeight(productWeight);		
			intlForms.addProduct(product);
			shpSvcOptions.setInternationalForms(intlForms);	
			
			/***************InternationalForms**********************/
			 

			shipRequest.setShipment(shpmnt);

			/** ************UPSSE************************** */
			UPSSecurity upss = new UPSSecurity();
			ServiceAccessToken_type0 upsSvcToken = new ServiceAccessToken_type0();
			upsSvcToken.setAccessLicenseNumber(accessKey);
			upss.setServiceAccessToken(upsSvcToken);
			UsernameToken_type0 upsSecUsrnameToken = new UsernameToken_type0();
			upsSecUsrnameToken.setUsername(userName);
			upsSecUsrnameToken.setPassword(password);
			upss.setUsernameToken(upsSecUsrnameToken);
			/** ************UPSSE***************************** */
			ShipmentResponse shipResponse = shipServiceStub.ProcessShipment(shipRequest, upss);

			System.out.println("Transaction Status: "
					+ shipResponse.getResponse().getResponseStatus()
							.getDescription());
			System.out.println("Shipment 1Z number: "
					+ shipResponse.getShipmentResults()
							.getShipmentIdentificationNumber());
			System.out.println(shipResponse.getShipmentResults()
					.getPackageResults()[0].getShippingLabel()
					.getGraphicImage());
			
			statusCode = shipResponse.getResponse().getResponseStatus().getCode();
            description = shipResponse.getResponse().getResponseStatus().getDescription();
			updateResultsToFile(statusCode, description);
			
			
		}catch (Exception e) {
			description=e.getMessage();
			statusCode=e.toString();
			if (e instanceof ShipmentErrorMessage){
				ShipmentErrorMessage shpErr = (ShipmentErrorMessage)e;
				System.out.println(shpErr.getFaultMessage().getErrorDetail()[0].getPrimaryErrorCode().getCode());
				System.out.println(shpErr.getFaultMessage().getErrorDetail()[0].getPrimaryErrorCode().getDescription());
				description=shpErr.getFaultMessage().getErrorDetail()[0].getPrimaryErrorCode().getDescription();
				statusCode=shpErr.getFaultMessage().getErrorDetail()[0].getPrimaryErrorCode().getCode();
			}
			
			updateResultsToFile(statusCode, description);
			e.printStackTrace();
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
	    		
	    		File outFile = new File(out_file_location);
	    		System.out.println("Output file deletion status: " + outFile.delete());
	    		outFile.createNewFile();
	    		System.out.println("Output file location: " + outFile.getCanonicalPath());
	    		bw = new BufferedWriter(new FileWriter(outFile));
	    		StringBuffer strBuf = new StringBuffer();
	    		strBuf.append("<ExecutionAt>");
	    		strBuf.append(Calendar.getInstance().getTime());
	    		strBuf.append("</ExecutionAt>\n");
	    		strBuf.append("<ToolOrWebServiceName>");
	    		strBuf.append(tool_or_webservice_name);
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

