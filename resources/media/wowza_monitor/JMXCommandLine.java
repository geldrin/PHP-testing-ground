import java.util.*;
import javax.management.*;
import javax.management.remote.*;

public class JMXCommandLine
{
    public static final String STREAMINGTYPES_TOTAL = "total";
    public static final String STREAMINGTYPES_CUPERTINO = "cupertino";
    public static final String STREAMINGTYPES_SMOOTH = "smooth";
    public static final String STREAMINGTYPES_RTMP = "rtmp";
    public static final String STREAMINGTYPES_RTSP = "rtsp";

    public static final String[][] countStrs = {
	{"Connections", STREAMINGTYPES_TOTAL},
	{"ConnectionsHTTPCupertino", STREAMINGTYPES_CUPERTINO},
	{"ConnectionsHTTPSmooth", STREAMINGTYPES_SMOOTH},
	{"ConnectionsRTMP", STREAMINGTYPES_RTMP},
	{"ConnectionsRTP", STREAMINGTYPES_RTSP},
    };

    public static final String[][] ioStrs = {
	{"IOPerformance", STREAMINGTYPES_TOTAL},
	{"IOPerformanceHTTPCupertino", STREAMINGTYPES_CUPERTINO},
	{"IOPerformanceHTTPSmooth", STREAMINGTYPES_SMOOTH},
	{"IOPerformanceRTMP", STREAMINGTYPES_RTMP},
	{"IOPerformanceRTP", STREAMINGTYPES_RTSP},
    };

    public static final int SESSIONPROTOCOL_SMOOTHSTREAMING = 0;
    public static final int SESSIONPROTOCOL_CUPERTINOSTREAMING = 1;

    public static final String MBEANNAME = "WowzaMediaServerPro";

    public static final String CMD_GETSERVERVERSION = "getServerVersion";
    public static final String CMD_STARTVHOST = "startVHost";
    public static final String CMD_STOPVHOST = "stopVHost";
    public static final String CMD_RELOADVHOSTCONFIG = "reloadVHostConfig";
    public static final String CMD_STARTAPPINSTANCE = "startAppInstance";
    public static final String CMD_TOUCHAPPINSTANCE = "touchAppInstance";
    public static final String CMD_SHUTDOWNAPPINSTANCE = "shutdownAppInstance";
    public static final String CMD_STARTMEDIACASTERSTREAM = "startMediaCasterStream";
    public static final String CMD_STOPMEDIACASTERSTREAM = "stopMediaCasterStream";
    public static final String CMD_RESETMEDIACASTERSTREAM = "resetMediaCasterStream";
    public static final String CMD_GETCONNECTIONCOUNTS = "getConnectionCounts";
    public static final String CMD_GETIOOUTBYTERATE = "getIOOutByteRate";
    public static final String CMD_GETIOINBYTERATE = "getIOInByteRate";

    public static final String DEFAULT_VHOST = "_defaultVHost_";
    public static final String DEFAULT_APPLICATION = "_defapp_";
    public static final String DEFAULT_APPINSTANCE = "_definst_";

    public static class AppConextName
    {
	String vhostName = null;
	String appName = null;
	String appInstName = null;

	public AppConextName()
	{
	}

	public AppConextName(String fullname, boolean startWithDefaults)
	{
	    if (startWithDefaults)
	    {
		vhostName = DEFAULT_VHOST;
		appName = DEFAULT_APPLICATION;
		appInstName = DEFAULT_APPINSTANCE;
	    }

	    int qloc = fullname.indexOf(":");
	    if (qloc >= 0)
	    {
		vhostName = fullname.substring(0, qloc);
		fullname = fullname.substring(qloc+1);
	    }
	    else
		vhostName = DEFAULT_VHOST;

	    if (fullname.length() > 0)
	    {
		appName = fullname;
		int sloc = fullname.indexOf("/");
		if (sloc >= 0)
		{
		    appName = fullname.substring(0, sloc);
		    appInstName = fullname.substring(sloc+1);
		}
	    }
	}

	String getObjName()
	{
	    String ret = "";

	    while(true)
	    {
		if (vhostName == null)
		    break;

		ret += "vHosts=VHosts,vHostName="+vhostName;

		if (appName == null)
		    break;

		ret += ",applications=Applications,applicationName="+appName;

		if (appInstName == null)
		    break;

		ret += ",applicationInstances=ApplicationInstances,applicationInstanceName="+appInstName;
		break;
	    }

	    return ret;
	}
    }

    public static void printUsage()
    {
	System.out.println("");
	System.out.println("Usage:");
	System.out.println("");
	System.out.println("[command] -[switch [value]...] [command] [params...]");
	System.out.println("");
	System.out.println("Switches:");
	System.out.println("");
	System.out.println("  -jmx  [jmx-url]");
	System.out.println("  -user [jmx-username]");
	System.out.println("  -pass [jmx-password]");
	System.out.println("");
	System.out.println("Commands:");
	System.out.println("");

	System.out.println("  "+CMD_GETSERVERVERSION);

	System.out.println("  "+CMD_STARTVHOST+" [vhost]");
	System.out.println("  "+CMD_STOPVHOST+" [vhost]");
	System.out.println("  "+CMD_RELOADVHOSTCONFIG+"");

	System.out.println("  "+CMD_STARTAPPINSTANCE+" [vhost:application/appInstance]");
	System.out.println("  "+CMD_TOUCHAPPINSTANCE+" [vhost:application/appInstance]");
	System.out.println("  "+CMD_SHUTDOWNAPPINSTANCE+" [vhost:application/appInstance]");

	System.out.println("  "+CMD_STARTMEDIACASTERSTREAM+" [vhost:application/appInstance] [stream-name] [mediacaster-type]");
	System.out.println("  "+CMD_STOPMEDIACASTERSTREAM+" [vhost:application/appInstance] [stream-name]");
	System.out.println("  "+CMD_RESETMEDIACASTERSTREAM+" [vhost:application/appInstance] [stream-name]");

	System.out.println("  "+CMD_GETCONNECTIONCOUNTS);
	System.out.println("  "+CMD_GETCONNECTIONCOUNTS+" [vhost:application/appInstance]");
	System.out.println("  "+CMD_GETCONNECTIONCOUNTS+" [vhost:application/appInstance] [stream-name]");

	System.out.println("  "+CMD_GETIOOUTBYTERATE);
	System.out.println("  "+CMD_GETIOOUTBYTERATE+" [vhost:application/appInstance]");

	System.out.println("  "+CMD_GETIOINBYTERATE);
	System.out.println("  "+CMD_GETIOINBYTERATE+" [vhost:application/appInstance]");

	System.out.println("");
    }

    public static long objToLong(Object valueObj)
    {
	long ret = 0;
	if (valueObj != null)
	{
	    try
	    {
		ret = Long.parseLong(valueObj.toString());
	    }
	    catch(Exception e)
	    {
	    }
	}
	return ret;
    }

    public static Object doInvoke(MBeanServerConnection connection, ObjectName connectsObjName, String cmdStr, Object[] arguments, String[] signature)
    {
	Object returnObj = null;
	try
	{
	    returnObj = connection.invoke(connectsObjName, cmdStr, arguments, signature);
	}
	catch(Exception e)
	{
	    System.out.println("ERROR: doInvoke: "+e.toString());
	}

	return returnObj;
    }

    public static Object doGetAttribute(MBeanServerConnection connection, ObjectName connectsObjName, String attributeName)
    {
	Object returnObj = null;
	try
	{
	    returnObj = connection.getAttribute(connectsObjName, attributeName);
	}
	catch(Exception e)
	{
	}

	return returnObj;
    }

    public static void main(String[] args)
    {

	try
	{
	    for(int i=0;i<args.length;i++)
		args[i] = args[i].trim();

	    String host = "localhost";
	    String username = null;
	    String password = null;
	    String jmxURL = "service:jmx:rmi://"+host+":8084/jndi/rmi://"+host+":8085/jmxrmi";

	    int argOffset = 0;
	    while(true)
	    {
		if (argOffset >= args.length)
		    break;

		if (!args[argOffset].startsWith("-"))
		    break;

		if (args[argOffset].startsWith("-host"))
		{
		    argOffset++;
		    host = args[argOffset];
		    jmxURL = "service:jmx:rmi://"+host+":8084/jndi/rmi://"+host+":8085/jmxrmi";
		}
		else if (args[argOffset].startsWith("-jmx"))
		{
		    argOffset++;
		    jmxURL = args[argOffset];
		}
		else if (args[argOffset].startsWith("-user"))
		{
		    argOffset++;
		    username = args[argOffset];
		}
		else if (args[argOffset].startsWith("-pass"))
		{
		    argOffset++;
		    password = args[argOffset];
		}

		argOffset++;
	    }

	    if (argOffset >= args.length)
	    {
		printUsage();
		return;
	    }

	    // create a connection URL
	    JMXServiceURL serviceURL = new JMXServiceURL(jmxURL);

	    // create a environment hash with username and password
	    Map<String, Object> env = new HashMap<String, Object>();

	    if (username != null && password != null)
	    {
		String[] creds = {username, password};
		env.put(JMXConnector.CREDENTIALS, creds);
	    }

	    JMXConnector connector = JMXConnectorFactory.connect(serviceURL, env);
	    MBeanServerConnection connection = connector.getMBeanServerConnection();

	    if (connection == null)
	    {
		System.out.println("ERROR: Cannot connect to JMX interface: "+jmxURL);
		return;
	    }

	    if (args[argOffset].equalsIgnoreCase(CMD_GETSERVERVERSION))
	    {
		String connectsName = MBEANNAME+":name=Server";
		ObjectName connectsObjName = new ObjectName(connectsName);

		Object attrObj = doGetAttribute(connection, connectsObjName, "version");
		if (attrObj != null)
		    System.out.println(attrObj);
		else
		    System.out.println("unknown");
	    }
	    else if (args[argOffset].equalsIgnoreCase(CMD_STARTVHOST))
	    {
		String connectsName = MBEANNAME+":name=Server";
		ObjectName connectsObjName = new ObjectName(connectsName);

		System.out.println(args[argOffset]+" "+args[argOffset+1]);
		Object[] arguments = {args[argOffset+1]};
		String[] signature = {"java.lang.String"};
		doInvoke(connection, connectsObjName, "startVHost", arguments, signature);
	    }
	    else if (args[argOffset].equalsIgnoreCase(CMD_STOPVHOST))
	    {
		String connectsName = MBEANNAME+":name=Server";
		ObjectName connectsObjName = new ObjectName(connectsName);

		System.out.println(args[argOffset]+" "+args[argOffset+1]);
		Object[] arguments = {args[argOffset+1]};
		String[] signature = {"java.lang.String"};
		doInvoke(connection, connectsObjName, "stopVHost", arguments, signature);
	    }
	    else if (args[argOffset].equalsIgnoreCase(CMD_RELOADVHOSTCONFIG))
	    {
		String connectsName = MBEANNAME+":name=Server";
		ObjectName connectsObjName = new ObjectName(connectsName);

		System.out.println(args[argOffset]);
		Object[] arguments = {};
		String[] signature = {};
		doInvoke(connection, connectsObjName, "reloadVHostConfig", null, null);
	    }
	    else if (args[argOffset].equalsIgnoreCase(CMD_STARTAPPINSTANCE))
	    {
		System.out.println(args[argOffset]+" "+args[argOffset+1]);

		AppConextName context = new AppConextName(args[argOffset+1], true);

		String connectsName = MBEANNAME+":vHosts=VHosts,vHostName="+context.vhostName+",name=VHost";
		ObjectName connectsObjName = new ObjectName(connectsName);

		Object[] arguments = {context.appName, context.appInstName};
		String[] signature = {"java.lang.String", "java.lang.String"};
		doInvoke(connection, connectsObjName, "startApplicationInstance", arguments, signature);
	    }
	    else if (args[argOffset].equalsIgnoreCase(CMD_TOUCHAPPINSTANCE))
	    {
		System.out.println(args[argOffset]+" "+args[argOffset+1]);

		AppConextName context = new AppConextName(args[argOffset+1], true);

		String connectsName = MBEANNAME+":vHosts=VHosts,vHostName="+context.vhostName+",name=VHost";
		ObjectName connectsObjName = new ObjectName(connectsName);

		Object[] arguments = {context.appName, context.appInstName};
		String[] signature = {"java.lang.String", "java.lang.String"};
		doInvoke(connection, connectsObjName, "touchApplicationInstance", arguments, signature);
	    }
	    else if (args[argOffset].equalsIgnoreCase(CMD_SHUTDOWNAPPINSTANCE))
	    {
		System.out.println(args[argOffset]+" "+args[argOffset+1]);

		AppConextName context = new AppConextName(args[argOffset+1], true);

		String connectsName = MBEANNAME+":vHosts=VHosts,vHostName="+context.vhostName+",applications=Applications,applicationName="+context.appName+",name=Application";
		ObjectName connectsObjName = new ObjectName(connectsName);

		Object[] arguments = {context.appInstName};
		String[] signature = {"java.lang.String"};
		doInvoke(connection, connectsObjName, "shutdownAppInstance", arguments, signature);
	    }
	    else if (args[argOffset].equalsIgnoreCase(CMD_STARTMEDIACASTERSTREAM))
	    {
		System.out.println(args[argOffset]+" "+args[argOffset+1]+" "+args[argOffset+2]+" "+args[argOffset+3]);

		AppConextName context = new AppConextName(args[argOffset+1], true);

		String connectsName = MBEANNAME+":vHosts=VHosts,vHostName="+context.vhostName+",applications=Applications,applicationName="+context.appName+",applicationInstances=ApplicationInstances,applicationInstanceName="+context.appInstName+",name=ApplicationInstance";
		ObjectName connectsObjName = new ObjectName(connectsName);

		Object[] arguments = {args[argOffset+2], args[argOffset+3]};
		String[] signature = {"java.lang.String", "java.lang.String"};
		doInvoke(connection, connectsObjName, "startMediaCasterStream", arguments, signature);
	    }
	    else if (args[argOffset].equalsIgnoreCase(CMD_STOPMEDIACASTERSTREAM))
	    {
		System.out.println(args[argOffset]+" "+args[argOffset+1]+" "+args[argOffset+2]);

		AppConextName context = new AppConextName(args[argOffset+1], true);

		String connectsName = MBEANNAME+":vHosts=VHosts,vHostName="+context.vhostName+",applications=Applications,applicationName="+context.appName+",applicationInstances=ApplicationInstances,applicationInstanceName="+context.appInstName+",name=ApplicationInstance";
		ObjectName connectsObjName = new ObjectName(connectsName);

		Object[] arguments = {args[argOffset+2]};
		String[] signature = {"java.lang.String"};
		doInvoke(connection, connectsObjName, "stopMediaCasterStream", arguments, signature);
	    }
	    else if (args[argOffset].equalsIgnoreCase(CMD_RESETMEDIACASTERSTREAM))
	    {
		System.out.println(args[argOffset]+" "+args[argOffset+1]+" "+args[argOffset+2]);

		AppConextName context = new AppConextName(args[argOffset+1], true);

		String connectsName = MBEANNAME+":vHosts=VHosts,vHostName="+context.vhostName+",applications=Applications,applicationName="+context.appName+",applicationInstances=ApplicationInstances,applicationInstanceName="+context.appInstName+",name=ApplicationInstance";
		ObjectName connectsObjName = new ObjectName(connectsName);

		Object[] arguments = {args[argOffset+2]};
		String[] signature = {"java.lang.String"};
		doInvoke(connection, connectsObjName, "resetMediaCasterStream", arguments, signature);
	    }
	    else if (args[argOffset].equalsIgnoreCase(CMD_GETCONNECTIONCOUNTS))
	    {
		StringBuffer outputBuf = new StringBuffer();

		String streamNamesStr = null;
		AppConextName context = null;
		if (args.length > (argOffset+2))
		{
		    context = new AppConextName(args[argOffset+1], true);
		    streamNamesStr = args[argOffset+2];
		}
		else if (args.length > (argOffset+1))
		    context = new AppConextName(args[argOffset+1], false);
		else
		    context = new AppConextName();

		if (streamNamesStr != null)
		{
		    String[] streamNames = streamNamesStr.split("[|]");

		    String contextStr = context.getObjName();
		    if (contextStr.length() > 0)
			contextStr += ",";
		    String connectsName = MBEANNAME+":"+contextStr+"name=ApplicationInstance";
		    ObjectName connectsObjName = new ObjectName(connectsName);

		    long total = 0;
		    long cupertinoTotal = 0;
		    long smoothTotal = 0;
		    long rtspTotal = 0;
		    long rtmpTotal = 0;
		    for(int i=0;i<streamNames.length;i++)
		    {
			String streamName = streamNames[i].trim();

			{
			    Object[] arguments = {SESSIONPROTOCOL_CUPERTINOSTREAMING, streamName};
			    String[] signature = {"int", "java.lang.String"};
			    Object attrObj = doInvoke(connection, connectsObjName, "getHTTPStreamerSessionCount", arguments, signature);
			    cupertinoTotal += objToLong(attrObj);
			}
			{
			    Object[] arguments = {SESSIONPROTOCOL_SMOOTHSTREAMING, streamName};
			    String[] signature = {"int", "java.lang.String"};
			    Object attrObj = doInvoke(connection, connectsObjName, "getHTTPStreamerSessionCount", arguments, signature);
			    smoothTotal += objToLong(attrObj);
			}
			{
			    Object[] arguments = {streamName};
			    String[] signature = {"java.lang.String"};
			    Object attrObj = doInvoke(connection, connectsObjName, "getPlayStreamCount", arguments, signature);
			    rtmpTotal += objToLong(attrObj);
			}
			{
			    Object[] arguments = {streamName};
			    String[] signature = {"java.lang.String"};
			    Object attrObj = doInvoke(connection, connectsObjName, "getRTPSessionCount", arguments, signature);
			    rtspTotal += objToLong(attrObj);
			}
		    }

		    total = cupertinoTotal+smoothTotal+rtmpTotal+rtspTotal;
		    System.out.println(STREAMINGTYPES_TOTAL+":"+total+" "+STREAMINGTYPES_CUPERTINO+":"+cupertinoTotal+" "+STREAMINGTYPES_SMOOTH+":"+smoothTotal+" "+STREAMINGTYPES_RTMP+":"+rtmpTotal+" "+STREAMINGTYPES_RTSP+":"+rtspTotal);

		}
		else
		{
		    for(int i=0;i<countStrs.length;i++)
		    {
			String contextStr = context.getObjName();
			if (contextStr.length() > 0)
			    contextStr += ",";
			String connectsName = MBEANNAME+":"+contextStr+"name="+countStrs[i][0];
			ObjectName connectsObjName = new ObjectName(connectsName);

			Object attrObj = doGetAttribute(connection, connectsObjName, "current");

			String valueStr = attrObj==null?"0":attrObj.toString();
			if (outputBuf.length() > 0)
			    outputBuf.append(" ");
			outputBuf.append(countStrs[i][1]+":"+valueStr);
		    }
		}

		System.out.println(outputBuf.toString());
	    }
	    else if (args[argOffset].equalsIgnoreCase(CMD_GETIOOUTBYTERATE) ||
		    args[argOffset].equalsIgnoreCase(CMD_GETIOINBYTERATE))
	    {
		StringBuffer outputBuf = new StringBuffer();

		AppConextName context = null;
		if (args.length > (argOffset+1))
		    context = new AppConextName(args[argOffset+1], false);
		else
		    context = new AppConextName();

		String attrValue = args[argOffset].equalsIgnoreCase(CMD_GETIOOUTBYTERATE)?"messagesOutBytesRate":"messagesInBytesRate";

		for(int i=0;i<ioStrs.length;i++)
		{
		    String contextStr = context.getObjName();
		    if (contextStr.length() > 0)
			contextStr += ",";
		    String connectsName = MBEANNAME+":"+contextStr+"name="+ioStrs[i][0];
		    ObjectName connectsObjName = new ObjectName(connectsName);

		    Object attrObj = doGetAttribute(connection, connectsObjName, attrValue);

		    String valueStr = attrObj==null?"0":attrObj.toString();
		    if (outputBuf.length() > 0)
			outputBuf.append(" ");
		    outputBuf.append(countStrs[i][1]+":"+valueStr);
		}

		System.out.println(outputBuf.toString());
	    }
	    else
		System.out.println("ERROR: Command not recognized: "+args[argOffset]);
	}
	catch (Exception e)
	{
	    System.out.println("ERROR: "+e.toString());
	}
    }
}