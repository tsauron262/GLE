/* 
 ** 
 ** Filename: JaxbShipConfirmClient.java 
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

import java.io.BufferedReader;
import java.io.BufferedWriter;
import java.io.ByteArrayInputStream;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileWriter;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.io.StringWriter;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLConnection;
import java.util.Properties;

import javax.xml.bind.JAXBContext;
import javax.xml.bind.Marshaller;
import javax.xml.bind.Unmarshaller;

import com.ups.xolt.codesamples.accessrequest.jaxb.AccessRequest;
import com.ups.xolt.codesamples.request.jaxb.BillShipperType;
import com.ups.xolt.codesamples.request.jaxb.CodeType;
import com.ups.xolt.codesamples.request.jaxb.InternationalFormsType;
import com.ups.xolt.codesamples.request.jaxb.LabelImageFormatCodeDescriptionType;
import com.ups.xolt.codesamples.request.jaxb.LabelPrintMethodCodeDescriptionType;
import com.ups.xolt.codesamples.request.jaxb.LabelSpecificationType;
import com.ups.xolt.codesamples.request.jaxb.PackageType;
import com.ups.xolt.codesamples.request.jaxb.PackageWeightType;
import com.ups.xolt.codesamples.request.jaxb.PackagingTypeType;
import com.ups.xolt.codesamples.request.jaxb.PaymentInformationType;
import com.ups.xolt.codesamples.request.jaxb.PrepaidType;
import com.ups.xolt.codesamples.request.jaxb.ProductType;
import com.ups.xolt.codesamples.request.jaxb.ProductWeightType;
import com.ups.xolt.codesamples.request.jaxb.RequestType;
import com.ups.xolt.codesamples.request.jaxb.ServiceType;
import com.ups.xolt.codesamples.request.jaxb.ShipFromAddressType;
import com.ups.xolt.codesamples.request.jaxb.ShipFromType;
import com.ups.xolt.codesamples.request.jaxb.ShipToAddressType;
import com.ups.xolt.codesamples.request.jaxb.ShipToType;
import com.ups.xolt.codesamples.request.jaxb.ShipmentConfirmRequest;
import com.ups.xolt.codesamples.request.jaxb.ShipmentServiceOptionsType;
import com.ups.xolt.codesamples.request.jaxb.ShipmentType;
import com.ups.xolt.codesamples.request.jaxb.ShipperAddressType;
import com.ups.xolt.codesamples.request.jaxb.ShipperType;
import com.ups.xolt.codesamples.request.jaxb.TransactionReferenceType;
import com.ups.xolt.codesamples.request.jaxb.UnitOfMeasurementType;
import com.ups.xolt.codesamples.response.jaxb.ShipmentConfirmResponse;

public class JaxbShipConfirmClient {
	
	private static final String LICENSE_NUMBER = "accesskey";
	private static final String USER_NAME = "username";
	private static final String PASSWORD = "password";
	private static final String ENDPOINT_URL="url";
	private static final String OUT_FILE_LOCATION = "out_file_location";
    private static Properties props = null;
	private static String description = null;
    static {
    	props = new Properties();
    	try{
    		props.load(new FileInputStream("./build.properties"));
    	}catch (Exception e) {
			description = e.toString();
			updateResultsToFile(description);
    		e.printStackTrace();
		}	
    }
    
    public static void main( String[] args ) {    
    	
    	String statusCode = null;
		String description = null;
		StringWriter strWriter = null;
        try {	    
        	
        	//Create JAXBContext and marshaller for AccessRequest object        			
        	JAXBContext accessRequestJAXBC = JAXBContext.newInstance(AccessRequest.class.getPackage().getName() );	            
			Marshaller accessRequestMarshaller = accessRequestJAXBC.createMarshaller();
			com.ups.xolt.codesamples.accessrequest.jaxb.ObjectFactory accessRequestObjectFactory = new com.ups.xolt.codesamples.accessrequest.jaxb.ObjectFactory();
			AccessRequest accessRequest = accessRequestObjectFactory.createAccessRequest();
			populateAccessRequest(accessRequest);
			 
			//Create JAXBContext and marshaller for RatingServiceSelectionRequest object
			//JAXBContext rateRequestJAXBC = JAXBContext.newInstance(RatingServiceSelectionRequest.class.getPackage().getName() );	            
			JAXBContext shipConfirmRequestJAXBC = JAXBContext.newInstance(ShipmentConfirmRequest.class.getPackage().getName() );
			
			
			Marshaller shipConfirmRequestMarshaller = shipConfirmRequestJAXBC.createMarshaller();
			com.ups.xolt.codesamples.request.jaxb.ObjectFactory requestObjectFactory = new com.ups.xolt.codesamples.request.jaxb.ObjectFactory();
			ShipmentConfirmRequest shipConfirmRequest = requestObjectFactory.createShipmentConfirmRequest();
			populateShipConfirmRequest(shipConfirmRequest);
			
			
			strWriter = new StringWriter();       		       
			accessRequestMarshaller.marshal(accessRequest, strWriter);
			shipConfirmRequestMarshaller.marshal(shipConfirmRequest, strWriter);
			strWriter.flush();
			strWriter.close();
			System.out.println("Request: " + strWriter.getBuffer().toString());
			
			String strResults =contactService(strWriter.getBuffer().toString());
	
			//Parse response object
			JAXBContext shipConfirmJAXBC = JAXBContext.newInstance(ShipmentConfirmResponse.class.getPackage().getName());
			Unmarshaller shipConfirmUnmarshaller = shipConfirmJAXBC.createUnmarshaller();
			ByteArrayInputStream input = new ByteArrayInputStream(strResults.getBytes());
			Object objResponse = shipConfirmUnmarshaller.unmarshal(input);
			ShipmentConfirmResponse shipconfirmResponse = (ShipmentConfirmResponse)objResponse;
			System.out.println("Response Status code: " + shipconfirmResponse.getResponse().getResponseStatusCode());
			System.out.println("Response Status Description: " + shipconfirmResponse.getResponse().getResponseStatusDescription());
			System.out.println("ShipmentID: " + shipconfirmResponse.getShipmentIdentificationNumber());
			System.out.println("Transportaion Charge: " + shipconfirmResponse.getShipmentCharges().getTransportationCharges().getCurrencyCode() + " " +
						shipconfirmResponse.getShipmentCharges().getTransportationCharges().getMonetaryValue());
			System.out.println("Service Option Charge: " + shipconfirmResponse.getShipmentCharges().getServiceOptionsCharges().getCurrencyCode() + " " +
					shipconfirmResponse.getShipmentCharges().getServiceOptionsCharges().getMonetaryValue());
			System.out.println("Total Charge: " + shipconfirmResponse.getShipmentCharges().getTotalCharges().getCurrencyCode() + " " +
					shipconfirmResponse.getShipmentCharges().getTotalCharges().getMonetaryValue());
			updateResultsToFile(strResults);		   
			
        } catch (Exception e) {
        		description=e.toString();
    			updateResultsToFile(description);
    			e.printStackTrace();
		} finally{
			try{
				if(strWriter != null){
					strWriter.close();
					strWriter = null;
				}
			}catch (Exception e) {
					description=e.toString();
					updateResultsToFile(description);
					e.printStackTrace();
			}
		}
    }    
    
	private static void populateShipConfirmRequest(ShipmentConfirmRequest shipRequest) {
		RequestType request = new RequestType();
		TransactionReferenceType transactionReference = new TransactionReferenceType();
		transactionReference.setCustomerContext("JAXB Test Client");
		request.setTransactionReference(transactionReference);
		
		request.setRequestOption("nonvalidate");
		shipRequest.setRequest(request);

		
		ShipmentType shpmnt = new ShipmentType();

		/** *******Shipper******************** */
		ShipperType shipper = new ShipperType();
		shipper.setName("Rocket J. Squirrel");
		shipper.setShipperNumber("222006");
		shipper.setPhoneNumber("1234567898");
		shipper.setAttentionName("Mr.ABC");
		
		ShipperAddressType shipperAddress = new ShipperAddressType();
		shipperAddress.setAddressLine1("2 South Main Street");
		shipperAddress.setCity("Timonium");
		shipperAddress.setPostalCode("21093");
		shipperAddress.setStateProvinceCode("MD");
		shipperAddress.setCountryCode("US");
		shipper.setAddress(shipperAddress);
		
		shpmnt.setShipper(shipper);
		/** ******Shipper********************* */

		/** ************ShipFrom****************** */
		ShipFromType shipFrom = new ShipFromType();
		shipFrom.setCompanyName("ABC Associates");
		shipFrom.setAttentionName("Mr.ABC");
		
		ShipFromAddressType shipFromAddress = new ShipFromAddressType();
		shipFromAddress.setAddressLine1("480 Parkton Plaza");
		shipFromAddress.setCity("Hoboken");
		shipFromAddress.setPostalCode("08805");
		shipFromAddress.setStateProvinceCode("NJ");
		shipFromAddress.setCountryCode("US");
		shipFrom.setAddress(shipFromAddress);
		
		shpmnt.setShipFrom(shipFrom);
		/** ***********ShipFrom********************* */

		/** ************ShipTo****************** */
		ShipToType shipTo = new ShipToType();
		shipTo.setCompanyName("DEF Associates");
		
		ShipToAddressType shipToAddress = new ShipToAddressType();
		shipToAddress.setAddressLine1("York Rd");
		shipToAddress.setCity("Hamburg");
		shipToAddress.setPostalCode("20354");
		shipToAddress.setCountryCode("DE");
		
		shipTo.setPhoneNumber("1234567898");
		shipTo.setAddress(shipToAddress);
		shipTo.setAttentionName("Mr.DEF");
		shpmnt.setShipTo(shipTo);
		/** ***********ShipTo********************* */

		/** ********Service********************** */
		ServiceType service = new ServiceType();
		service.setCode("08");
		shpmnt.setService(service);
		/** ********Service********************** */

		/** ******************Package***************** */
		PackageType pkg1 = new PackageType();
		PackagingTypeType pkgingType = new PackagingTypeType();
		pkgingType.setCode("02");
		pkg1.setPackagingType(pkgingType);
		PackageType[] pkgArray = { pkg1 };
		PackageWeightType weight = new PackageWeightType();
		weight.setWeight("1");
		
		UnitOfMeasurementType shpUnitOfMeas = new UnitOfMeasurementType();
		shpUnitOfMeas.setCode("LBS");
		shpUnitOfMeas.setDescription("Pounds");
		weight.setUnitOfMeasurement(shpUnitOfMeas);
		pkg1.setPackageWeight(weight);
		shpmnt.getPackage().add(pkgArray[0]);
		
		/** ******************Package***************** */

		/** *************Payment Information***************** */
		PaymentInformationType payInfo = new PaymentInformationType();
		PrepaidType prepaid = new PrepaidType();
		BillShipperType billShipper = new BillShipperType();
		billShipper.setAccountNumber("222006");
		prepaid.setBillShipper(billShipper);
		payInfo.setPrepaid(prepaid);
		shpmnt.setPaymentInformation(payInfo);
		/** *************Payment Information***************** */

		/** **********Label Specification ******************** */
		LabelSpecificationType labelSpecType = new LabelSpecificationType();
		LabelImageFormatCodeDescriptionType labelImageFormat = new LabelImageFormatCodeDescriptionType();
		labelImageFormat.setCode("GIF");
		labelImageFormat.setDescription("GIF");
		labelSpecType.setLabelImageFormat(labelImageFormat);
		
		LabelPrintMethodCodeDescriptionType labelPrintmethod = new LabelPrintMethodCodeDescriptionType();
		labelPrintmethod.setCode("GIF");
		labelPrintmethod.setDescription("gif file");
		labelSpecType.setLabelPrintMethod(labelPrintmethod);
		
		labelSpecType.setHTTPUserAgent("Mozilla/4.5");
		shipRequest.setLabelSpecification(labelSpecType);
		/** ***********Label Specification********************* */
		
		/***************InternationalForms********************/
		ShipmentServiceOptionsType shpSvcOptions = new ShipmentServiceOptionsType();
		InternationalFormsType intlForms = new InternationalFormsType();
		intlForms.getFormType().add("03");
		intlForms.setFormGroupIdName("CO Form");
		intlForms.setExportDate("20080819");
		intlForms.setExportingCarrier("funship");
		intlForms.setCurrencyCode("USD");
		ProductType product = new ProductType();
		String[] productDesc = {"prod1"};
		product.getDescription().add("prod1");
		product.setOriginCountryCode("US");
		product.setMarksAndNumbers("ABC123");
		product.setNumberOfPackagesPerCommodity("6");
		ProductWeightType productWeight = new ProductWeightType();
		productWeight.setWeight("58");
		UnitOfMeasurementType uom = new UnitOfMeasurementType();
		CodeType codeType = new CodeType();
		codeType.setCode("KGS");
		codeType.setDescription("KiloGrams");
		
		productWeight.setUnitOfMeasurement(codeType);
		product.setProductWeight(productWeight);	
		ProductType[] productArray = {product};
		intlForms.getProduct().add(productArray[0]);
		//intlForms.setProduct(productArray);
		shpSvcOptions.setInternationalForms(intlForms);	
		shpmnt.setShipmentServiceOptions(shpSvcOptions);			
		/***************InternationalForms**********************/
		
		shpmnt.setDescription("Some Goods");
		shipRequest.setShipment(shpmnt);
		
	}

	private static String contactService(String xmlInputString) throws Exception{		
		String outputStr = null;
		OutputStream outputStream = null;
		try {

			URL url = new URL(props.getProperty(ENDPOINT_URL));
			
			HttpURLConnection connection = (HttpURLConnection) url.openConnection();
			System.out.println("Client established connection with " + url.toString());
			// Setup HTTP POST parameters
			connection.setDoOutput(true);
			connection.setDoInput(true);
			connection.setUseCaches(false);
			
			outputStream = connection.getOutputStream();		
			outputStream.write(xmlInputString.getBytes());
			outputStream.flush();
			outputStream.close();
			System.out.println("Http status = " + connection.getResponseCode() + " " + connection.getResponseMessage());
			
			outputStr = readURLConnection(connection);	
			System.out.println(outputStr);
		} catch (Exception e) {
			System.out.println("Error sending data to server");
			throw e;
		} finally {						
			if(outputStream != null){
				outputStream.close();
				outputStream = null;
			}
		}		
		return outputStr;
	}
	
	/**
	 * This method read all of the data from a URL connection to a String
	 */

	public static String readURLConnection(URLConnection uc) throws Exception {
		StringBuffer buffer = new StringBuffer();
		BufferedReader reader = null;
		try {
			reader = new BufferedReader(new InputStreamReader(uc.getInputStream()));
			int letter = 0;			
			reader.readLine();
			while ((letter = reader.read()) != -1){
				buffer.append((char) letter);
			}
			reader.close();
		} catch (Exception e) {
			System.out.println("Could not read from URL: " + e.toString());
			throw e;
		} finally {
			if(reader != null){
				reader.close();
				reader = null;
			}
		}
		return buffer.toString();
	}

    /**
     * Populates the access request object.
     * @param accessRequest
     */
    private static void populateAccessRequest(AccessRequest accessRequest){
    	accessRequest.setAccessLicenseNumber(props.getProperty(LICENSE_NUMBER));
    	accessRequest.setUserId(props.getProperty(USER_NAME));
    	accessRequest.setPassword(props.getProperty(PASSWORD));
    }
   
    /**
     * This method updates the XOLTResult.xml file with the received status and description
     * @param response
     */
    private static void updateResultsToFile(String response){
    	BufferedWriter bw = null;
    	try{    		
    		File outFile = new File(props.getProperty(OUT_FILE_LOCATION));
    		System.out.println("Output file deletion status: " + outFile.delete());
    		outFile.createNewFile();
    		System.out.println("Output file location: " + outFile.getCanonicalPath());
    		bw = new BufferedWriter(new FileWriter(outFile));
    		StringBuffer strBuf = new StringBuffer();
     		strBuf.append(response);
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


